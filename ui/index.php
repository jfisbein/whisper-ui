<?php
function getParamOrValue($paramName, $value)
{
    $selectedValue = $value;
    if (isset($_POST[$paramName])) {
        $selectedValue = trim($_POST[$paramName]);
    } elseif (isset($_GET[$paramName])) {
        $selectedValue = trim($_GET[$paramName]);
    } elseif (isset($_COOKIE[$paramName])) {
        $selectedValue = trim($_COOKIE[$paramName]);
    }

    return $selectedValue;
}

function createJobFile($target_file, $file_dir, $language, $model)
{
    $duration = getDuration($target_file);
    $file_name = basename($target_file) . ".json";
    $file = fopen($file_dir . "/" . $file_name, "w") or die("Unable to open file!");
    $data = array(
        'created_at' => date('Y-m-d H:i:s'),
        'transcription_status' => 'pendiente', // 'pendiente', 'procesando', 'completado', 'error'
        'transcription_start_date' => null,
        'transcription_finish_date' => null,
        'audio_file' => basename($target_file),
        'audio_file_duration' => $duration,
        'transcription_file' => null,
        'language' => $language,
        'model' => $model
    );
    fwrite($file, json_encode($data));
}

function getDuration($file)
{
    return shell_exec("ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 '" . $file . "'");
}

function format($seconds) {
    $seconds = round($seconds);
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds / 60) % 60);
    $seconds = $seconds % 60;

    return sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
}

$target_dir = "jobs/pending/";
$language = getParamOrValue('language', 'es');
$model = getParamOrValue('model', 'large-v3');
$debug = getParamOrValue('debug', 'false');

if (isset($_FILES["fileToTranscribe"])) {
    $target_file = $target_dir . basename($_FILES["fileToTranscribe"]["name"]);
    $completed_file = "jobs/completed/" . basename($_FILES["fileToTranscribe"]["name"]);
    # check if file exists
    if (file_exists($target_file) || file_exists($completed_file)) {
        echo "Sorry, file already exists.";
        exit();
    }
    move_uploaded_file($_FILES["fileToTranscribe"]["tmp_name"], $target_file);
    createJobFile($target_file, $target_dir, $language, $model);
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Transcriptor</title>
    <meta http-equiv="refresh" content="600">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>

<body class="m-4">
    <h1>Transcriptor</h1>

    <h2>Subir archivo</h2>
    <form action="index.php" method="post" enctype="multipart/form-data">
        <div class="m-4">
            <label for="file" class="form-label">Archivo de audio a transcribir</label>
            <input type="file" class="form-control" id="file" name="fileToTranscribe">
        </div>
        <!-- Add radio to select model between base and medium -->
        <div class="m-4">
            <label for="model" class="form-label">Modelo</label>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="model" id="small" value="small" <?php if ($model == 'small') echo 'checked'; ?>>
                <label class="form-check-label" for="small">Small</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="model" id="medium" value="medium" <?php if ($model == 'medium') echo 'checked'; ?>>
                <label class="form-check-label" for="medium">Medium</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="model" id="large" value="large-v3" <?php if ($model == 'large-v3') echo 'checked'; ?>>
                <label class="form-check-label" for="large">Large</label>
            </div>
        <button type="submit" class="btn btn-primary mt-4">Subir Archivo</button>
    </form>
    
    <hr/>

    <h2>Transcripciones pendientes</h2>
    <table class="table" class="m-4">
        <thead>
            <tr>
                <th scope="col">Archivo</th>
                <th scope="col">Modelo</th>
                <th scope="col">Fecha creación</th>
                <th scope="col">Duración audio</th>
                <th scope="col">Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $files = glob($target_dir . '/*.json');
            foreach ($files as $file) {
                $data = json_decode(file_get_contents($file), true);
                if ($data['transcription_status'] != 'procesando') {
                    echo "<tr>";
                    echo "<td> <a href='jobs/pending/" . $data['audio_file'] . "'>" . $data['audio_file'] . "</a></td>";
                    echo "<td>" . $data['model'] . "</td>";
                    echo "<td>" . $data['created_at'] . "</td>";
                    echo "<td>" . format($data['audio_file_duration']) . "</td>";
                    echo "<td>" . $data['transcription_status'] . "</td>";
                    echo "</tr>";
                }
            }
            ?>
        </tbody>
    </table>

    <hr />

    <h2>Transcripciones en curso</h2>
    <table class="table" class="m-4">
        <thead>
            <tr>
                <th scope="col">Archivo</th>
                <th scope="col">Modelo</th>
                <th scope="col">Fecha creación</th>
                <th scope="col">Fecha comienzo de procesado</th>
                <th scope="col">Duración audio</th>
                <th scope="col">Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $files = glob($target_dir . '/*.json');
            foreach ($files as $file) {
                $data = json_decode(file_get_contents($file), true);
                if ($data['transcription_status'] == 'procesando') {
                    echo "<tr>";
                    echo "<td> <a href='jobs/pending/" . $data['audio_file'] . "' target='_blank'>" . $data['audio_file'] . "</a></td>";
                    echo "<td>" . $data['model'] . "</td>";
                    echo "<td>" . $data['created_at'] . "</td>";
                    echo "<td>" . $data['transcription_start_date'] . "</td>";
                    echo "<td>" . format($data['audio_file_duration']) . "</td>";
                    echo "<td>" . $data['transcription_status'] . "</td>";
                    echo "</tr>";
                }
            }
            ?>
        </tbody>
    </table>

    <hr />

    <h2>Transcipciones completadas</h2>
    <table class="table" class="m-4">
        <thead>
            <tr>
                <th scope="col">Archivo</th>
                <th scope="col">Modelo</th>
                <th scope="col">Fecha creación</th>
                <th scope="col">Fecha de comienzo de procesado</th>
                <th scope="col">Fecha de fin de procesado</th>
                <th scope="col">Duración audio</th>
                <th scope="col">Estado</th>
                <th scope="col">Descargar</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $files = glob("jobs/completed/*.json");
            foreach ($files as $file) {
                $data = json_decode(file_get_contents($file), true);
                echo "<tr>";
                echo "<td> <a href='jobs/completed/" . $data['audio_file'] . "' target='_blank'>" . basename($data['audio_file']) . "</a></td>";
                echo "<td>" . $data['model'] . "</td>";
                echo "<td>" . $data['created_at'] . "</td>";
                echo "<td>" . $data['transcription_start_date'] . "</td>";
                echo "<td>" . $data['transcription_finish_date'] . "</td>";
                echo "<td>" . format($data['audio_file_duration']) . "</td>";
                echo "<td>" . $data['transcription_status'] . "</td>";
                echo "<td><a href='jobs/completed/" . $data['transcription_file'] . "' download>" . $data['transcription_file'] . "</a></td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>