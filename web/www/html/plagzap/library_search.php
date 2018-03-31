<html>
<head>
    <title>Query library</title>
    <meta charset="utf8" />
    <script src="https://www.gstatic.com/charts/loader.js"></script>
    <script>google.charts.load("current", {packages:["corechart", "bar", "gauge"]});</script>
    <style>
        a.marker {
            display: inline-block;
            width: 0;
            height: 0.75em;
            overflow: hidden;
            text-indent: 10em;
            border-left: 0.75em solid;
            padding-left: 0.05em;
        }
        @media print {
            a.marker {
                display: inline;
                text-indent: initial;
                padding-left: 0.25em;
            }
            form {
                display: none;
            }
            div.result {
                page-break-after: always;
            }
        }
        @media screen {
            @media (min-width: 600px) {
                div.sources {
                    position: sticky;
                    width: 25%;
                    top: 0;
                    float: right;
                    overflow: auto;
                    height: 100%;
                }

                div.document {
                    width: 75%;
                }

            }
        }
        table, tr, td, th {
            border: 1px solid;
        }
        td {
            text-align: center;
        }
        col.originale {
            background: lightgreen;
        }
        col.copiate {
            background: lightpink;
        }
    </style>
</head>
<body>
<form enctype="multipart/form-data" method="post">
    <p>Files:<br><input type="file" name="file[]" /><br>
        <input type="file" name="file[]" /><br>
        <input type="file" name="file[]" /><br>
        <input type="file" name="file[]" /><br>
        <input type="file" name="file[]" />
    </p>
    <p>Expresii intre <input type="number" name="expmin" value="<?php echo empty($_POST['expmin']) ? 3 : $_POST['expmin']?>" min="2" max="20" size="3"/> si <input type="number" name="expmax" value="<?php echo empty($_POST['expmax'])? 5 : $_POST['expmax']?>" min="2" max="20" size="3"> cuvinte.</p>
    <p><label>Afiseaza surse multiple: <input type="checkbox" name="multisource" <?php if (!empty($_POST['multisource'])) echo 'checked="checked"' ?>/></label></p>
    <p>
        <input type="submit" name="update" value="Update" />
    </p>
</form>
<?php
/**
 * Created by PhpStorm.
 * User: abautu
 * Date: 12.02.2018
 * Time: 05:31
 */
require_once('config.php');
if ($_POST)
{
    function escape_solr_text($text) {
        static $regexp;
        if (is_null($regexp)) {
            $regexp = '@([' . preg_quote('+&|!(){}[]^"\'~*?:\/-') . '])@';
        }
        return preg_replace($regexp, '\\\$1', $text);
    }
    $expmin = intval($_POST['expmin']);
    $expmax = intval($_POST['expmax']);
    $rows = empty($_POST['multisource']) ? 1 : 10;

    $ch = curl_init();
//    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

    foreach($_FILES['file']['name'] as $i => $name) {
        if (empty($name)) {
            continue;
        }
        if ($_FILES['file']['error'][$i]) {
            echo "<p>Error in uploading file $name";
            continue;
        }
        $data = array(
            'extractOnly' => 'true',
            'extractFormat' => 'text',
            'resource.name' => $name,
            'file' => curl_file_create($_FILES['file']['tmp_name'][$i], $_FILES['file']['type'][$i], $_FILES['file']['name'][$i]),
        );
        curl_setopt($ch, CURLOPT_URL, $SOLR_URL . "/update/extract");
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $result = json_decode(curl_exec($ch));
        if (is_null($result)) {
            echo "<p>Failed to connect to server";
            continue;
        }
        if (!empty($result->responseHeader->status)) {
            echo "<p>Failed to send file $name to server";
            continue;
        }
        $document = $result->$name;

        curl_setopt($ch, CURLOPT_URL, $SOLR_URL . "/query");
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $result = curl_exec($ch);

        $sources = array();
        $words_doctotal = str_word_count($document);
        $words_scanned = 0;
        $words_copied = 0;
        $chars_doctotal = strlen($document);
        $chars_scanned = 0;
        $chars_copied = 0;
        $samples_scanned = 0;
        $samples_copied = 0;
        $current_sources = null;
        $output = '';

        $document = preg_split('/\n+/', trim($document));
        foreach ($document as $text) {
            while (true) {
                if (!preg_match('/(\S*[\w]\S*([\s.,-]+|$)+){' . $expmin. ',' . $expmax . '}/', $text, $matches, PREG_OFFSET_CAPTURE)) {
                    break;
                }
                list($sample, $possample) = $matches[0];
                $presample = substr($text, 0, $possample);
                $text = substr($text, $possample + strlen($sample));

                ++$samples_scanned;
                $words_scanned += str_word_count($sample);
                $chars_scanned += strlen($sample);

                $output .= htmlentities($presample);
                $data = array(
                    'q' => '"' . escape_solr_text($sample) . '"',
                    'fl' => 'id,resourcename,description',
                    'rows' => $rows,
                );
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                $result = json_decode(curl_exec($ch));

                if ($result->response->numFound > 0) {
                    ++$samples_copied;
                    $words_copied += str_word_count($sample);
                    $chars_copied += strlen($sample);
                    $new_sources = array();
                    foreach ($result->response->docs as $source) {
                        $sources[$source->id]['color'] = substr($source->id, 0, 6);
                        $sources[$source->id]['name'] = $source->resourcename[0];
                        $sources[$source->id]['description'] = $source->description[0];
                        $sources[$source->id]['words'] += str_word_count($sample);
                        $sources[$source->id]['samples']++;
                        $new_sources[$source->id] = TRUE;
                    }

                    if ($current_sources != $new_sources) {
                        $current_sources = $new_sources;
                        foreach(array_keys($current_sources) as $id) {
                            $output .= sprintf('<a href="#%d_%s" style="border-left-color: #%s" class="marker" title="%s">%s</a>', $i, $id, $sources[$id]['color'], $sources[$id]['name'], $id);
                        }
                    }

                    $output .= '<span style="font-weight: bold; border-bottom: 1px solid red; background-color: lightpink;">';
                    $output .= htmlentities($sample);
                    $output .= '</span>';
                } else {
                    $output .= htmlentities($sample);
                }
            }
            $output .= htmlentities($text);
            $output .= '<br>';
        }
        $chars_original = $chars_scanned - $chars_copied;
        $chars_original_ratio = $chars_original/$chars_scanned;
        $words_original = $words_scanned - $words_copied;
        $words_original_ratio = $words_original/$words_scanned;
        $samples_original = $samples_scanned-$samples_copied;
        $samples_original_ratio = $samples_original/$samples_scanned;

        uasort($sources, function ($a, $b) { return $b['words'] - $a['words']; });

        echo '<div class="result">';
        echo '<div class="summary">';
        echo '<h1>Analiza documentului "', htmlentities($name), '"</h1>';

        printf('<div id="summary_chart%d" style="display: inline-block; width: 150px; height: 150px;"></div>', $i);
        echo '<script>';
        printf('function drawSummaryChart%d() {', $i);
        echo 'var data = google.visualization.arrayToDataTable([';
        echo '["Label", "Value"],';
        printf('["Original", %.2f],', 100*$words_original_ratio);
        echo ']);';
        echo 'var options = {redFrom: 0, redTo: 10, yellowFrom:11, yellowTo: 25, greenFrom:26, greenTo: 100, max: 100, minorTicks: 5};';
        printf('new google.visualization.Gauge(document.getElementById("summary_chart%d")).draw(data, options)', $i);
        echo '}';
        printf('google.charts.setOnLoadCallback(drawSummaryChart%d);', $i);
        echo '</script>';

        echo '<table style="display: inline-block">';
        echo '<col class="metric" /><col class="document"/><col class="scanate"/><col class="originale"/><col class="copiate"/>';
        echo '<tr><th>Metrica</th><th>In document</th><th>Verificate</th><th>Originale</th><th>Non-originale</th>';
        printf('<tr><th>#Caractere</th><td>%d</td><td>%d</td><td>%d</td><td>%d</td>', $chars_doctotal, $chars_scanned, $chars_original, $chars_copied);
        printf('<tr><th>%% Caractere</th><td>%.2f</td><td>%.2f</td><td>%.2f</td><td>%.2f</td>', 100*$chars_doctotal/$chars_scanned, 100, 100*$chars_original_ratio, 100*(1-$chars_original_ratio));
        printf('<tr><th># Cuvinte</th><td>%d</td><td>%d</td><td>%d</td><td>%d</td>', $words_doctotal, $words_scanned, $words_original, $words_copied);
        printf('<tr><th>%% Cuvinte</th><td>%.2f%%</td><td>%.2f%%</td><td>%.2f%%</td><td>%.2f%%</td>', 100*$words_doctotal/$words_scanned, 100, 100*$words_original_ratio, 100*(1-$words_original_ratio));
        printf('<tr><th># Expresii</th><td>%d</td><td>%d</td><td>%d</td><td>%d</td>', $samples_scanned, $samples_scanned, $samples_original, $samples_copied);
        printf('<tr><th>%% Expresii</th><td>%.2f%%</td><td>%.2f%%</td><td>%.2f%%</td><td>%.2f%%</td>', 100, 100, 100*$samples_original_ratio, 100*(1-$samples_original_ratio));
        echo '</table>';

        printf('<div id="sources_chart%d" style="display: inline-block; width: 600px; height: 150px;"></div>', $i);
        echo '<script>';
        printf('function drawSourcesChart%d() {', $i);
        echo 'var data = google.visualization.arrayToDataTable([';
        echo '["Source", "Procent", { role: "style" }],';
        $showChart = FALSE;
        foreach($sources as $id => $info) {
            $procent = 100*$info['words']/$words_scanned;
            if ($procent >= 1) {
                printf("['%s', %.1f, '#%s'],", $info['name'], $procent, $info['color']);
                $showChart = TRUE;
            }
        }
        echo ']);';
        echo 'var options = {title: "Contributia surselor", legend: { position: "none" }, bars: "horizontal", hAxis: { title: "% Copiat", maxValue: 100 }, vAxis: { title: "Sursa" },};';
        printf('new google.visualization.BarChart(document.getElementById("sources_chart%d")).draw(data, options)', $i);
        echo '}';
        if ($showChart) {
            printf('google.charts.setOnLoadCallback(drawSourcesChart%d);', $i);
        }
        echo '</script>';

        echo '</div>';

        echo '<div class="sources">';
        echo '<h2>Surse (in ordinea similaritatii)</h2>';
        echo '<ol>';
        foreach($sources as $id => $info) {
            printf('<li><a class="marker" id="%d_%s" style="border-left-color: #%s">%s</a>', $i, $id, $info['color'], $id);
            echo ' ', htmlentities($info['name']), ' ';
            printf('(%d cuvinte / %d expresii copiate, adica %.1f%% din documentul scanat)', $info['words'], $info['samples'], 100*$info['words']/$words_scanned);
            echo ' - ', htmlentities($info['description']);
        }
        echo '</ol>';
        echo '</div>';
        echo '<div class="document">';
        echo '<h2>Continut</h2>';
        echo '<p>';
        echo $output;
        echo '</p>';
        echo '</div>';
        echo '</div>';

        $sha1_file = sha1_file($_FILES['file']['tmp_name'][$i]);
        @mkdir($FILE_DIR,0777,TRUE);
        $uploadfile = $FILE_DIR . DIRECTORY_SEPARATOR . $sha1_file;
        if(!is_file($uploadfile . '.data')) {
            @move_uploaded_file($_FILES["file"]["tmp_name"][$i], $uploadfile . '.data');
        }
        file_put_contents($uploadfile. '_'.date('YmdHis') .'_scanned.meta', $name);
    }
    curl_close ($ch);

}
?>
<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"></script>
<script>
    $('a.marker[href]').hover(
        function(){
            var item = $('#' + this.href.split('#')[1]).parent().css('background-color', 'lightpink')
            item.parents('.sources').scrollTop(item[0].offsetTop);
        },
        function(){
            $('#' + this.href.split('#')[1]).parent().css('background-color', '')
        }
    )
</script>
</body>
</html>
