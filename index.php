<?php

require __DIR__ . '/vendor/autoload.php';

const LOG_FILE = 'logs.txt';

function add_logs($ext): void
{
  file_put_contents(LOG_FILE, print_r(date('Y-m-d H:i:s'), true) . ':' . PHP_EOL,
    FILE_APPEND | LOCK_EX);
  file_put_contents(LOG_FILE, print_r($ext, true) . PHP_EOL,
    FILE_APPEND | LOCK_EX);
}


function build_path(array $path_items): string
{
  $folder = '';

  foreach ($path_items as $path_item) {
    $folder .= $folder ? DIRECTORY_SEPARATOR . $path_item : $path_item;
  }

  return $folder;
}

function build_uri_from_path(string $path): string
{
  $path_arr = explode(DIRECTORY_SEPARATOR, $path);
  return implode('/', $path_arr);
}

function check_dest_folder(array $path_items): string
{
  $folder = '';

  foreach ($path_items as $path_item) {
    $folder .= $folder ? DIRECTORY_SEPARATOR . $path_item : $path_item;
  }

  if (!file_exists($folder)) {
    mkdir($folder, 0644, true);
  }

  return $folder;
}

function remove_file(string $file_path): bool
{
  if (file_exists($file_path)) {
    return unlink($file_path);
  } else {
    return false;
  }
}

/**
 * Generate random file name for folder
 *
 * @param int $length
 * @param string $directory
 * @param string $extension
 * @return string
 */
function random_filename(int $length, string $directory = '', string $extension = ''): string
{
  // default to this files directory if empty...
  $dir = !empty($directory) && is_dir($directory) ? $directory : dirname(__FILE__);

  do {
    $key = '';
    $keys = array_merge(range(0, 9), range('a', 'z'));

    for ($i = 0; $i < $length; $i++) {
      $key .= $keys[array_rand($keys)];
    }
  } while (file_exists($dir . '/' . $key . (!empty($extension) ? '.' . $extension : '')));

  return $key . (!empty($extension) ? '.' . $extension : '');
}

function http_response(int $code, array $body): int|bool
{
  header("Content-type:application/json");
  echo json_encode($body);

  return http_response_code($code);
}

$response = [];

// Upload images
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['images'])) {
  // Get params for folder destination
  $casino = (isset($_POST['casino'])) ? $_POST['casino'] : false;
  $env = (isset($_POST['env'])) ? $_POST['env'] : false;
  $category = (isset($_POST['category'])) ? $_POST['category'] : false;

  $folder = '';

  // check if folder for files exist or create it if not
  if ($casino && $category && $env) {
    // create casino folder path
    $base_folder = build_path([
      __DIR__,
      $casino,
      $env,
      $category,
    ]);

    $current_date = new DateTime();

    // create relative category folder path
    $relative_folder = build_path([
      $current_date->format('Y'),
      $current_date->format('m')
    ]);

    $folder = check_dest_folder([$base_folder, $relative_folder]);

    if (!$folder) {
      return http_response_code(400);
    }
  } else {
    return http_response_code(400);
  }

  // upload, generate names and move files
  foreach ($_FILES['images']['name'] as $index => $image) {
    $file_name = random_filename(32, $folder, pathinfo($image, PATHINFO_EXTENSION));

    $upload_file = build_path([$folder, basename($file_name)]);

    $tmp_file = $_FILES['images']['tmp_name'][$index];
    $dst_file = $upload_file;

    move_uploaded_file($tmp_file, $dst_file);

    $response[$image] = build_uri_from_path(str_replace($base_folder, '', $upload_file));
  }

  return http_response(200, $response);
}

// delete images
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'DELETE' && isset($_REQUEST['image'])) {
  // Get params for folder destination
  $casino = (isset($_REQUEST['casino'])) ? $_REQUEST['casino'] : false;
  $env = (isset($_REQUEST['env'])) ? $_REQUEST['env'] : false;
  $category = (isset($_REQUEST['category'])) ? $_REQUEST['category'] : false;

  $image = (isset($_REQUEST['image'])) ? $_REQUEST['image'] : false;

  // check if folder for files exist or create it if not
  if ($casino && $category && $env) {
    // build image paths
    $image_file = build_path([
      __DIR__,
      $casino,
      $env,
      $category,
      $image
    ]);

    if (remove_file($image_file)) {
      $response['message'] = sprintf('File %s successfully deleted', $image);
      return http_response(200, $response);
    } else {
      $response['message'] = sprintf('File %s not deleted or not exist', $image);
      return http_response(400, $response);
    }
  } else {
    return http_response_code(400);
  }

}
?>

<h1>File Storage</h1>
