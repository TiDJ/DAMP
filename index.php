<?php
include("config.php");

// Not in config.json for security reasons, refere to config.php
$mysql_version = "Unknown";
$php_testing_version = phpversion();
$php_testing_version = substr($php_testing_version,0,1 );

if( $php_testing_version=="7") {
    $mysqli = mysqli_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASSWORD);
    if (!mysqli_connect_errno()) {
        $mysql_version = mysqli_get_server_info($mysqli);
    }
} else if (@mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASSWORD)) {
    $mysql_version = mysql_get_server_info();
} else {
    preg_match("([0-9\.]+)", @mysqli_get_server_info(), $match);
    $mysql_version = isset($match[0]) ? $match[0] : "Unknown";
}
$projectsListIgnore = explode(",",IGNORED_PROJECTS);

// Translation, in English if not found
$messages = array(
   "fr" => array(
      "langue" => "Francais",
      // Header
      "Launch fastly your projects with Dashboard Apache Mysql PHP" => "Lancez rapidement vos projects avec Dashboard Apache Mysql PHP",
      "Projects" => "Projets",
      "Configuration" => "Configuration",
      "Customization" => "Personnalisation",
      "Show full configuration" => "Déplier les configurations",
      "I got" => "J'ai",
      "with" => "avec",
      "on" => "sur",
      // Global form
      "Save" => "Enregistrer",
      "General settings" => "Réglages généraux",
      "Add manually" => "Ajouter manuellement",
      "First favorite" => "Premier favoris",
      "Second favorite" => "Deuxième favoris",
      "Third favorite" => "Troisième favoris",
      "Fourth favorite" => "Quatrième favoris",
      // Solo Project Form
      "Project's name" => "Nom du projet",
      "Default" => "Par défaut",
      "Finished" => "Terminé",
      "Personnal" => "Personnel",
      "Professional" => "Professionnel",
      "Others" => "Autre",
      "Project's badges" => "Badges du projet",
      "Project's URL" => "URLs du projet",
      "Description" => "Description",
      "First link" => "Premier lien",
      "Second link" => "Deuxième lien",
      "Third link" => "Troisième lien",
      "Fourth link" => "Quatrième lien",
      // Footer
      "Back to top" => "Revenir en haut",
   )
);

/**
 * [phpinfo_array description]
 * @return [type] [description]
 */
function phpinfo_array()
{
    ob_start();
    phpinfo();
    $info_arr = array();
    $info_lines = explode("\n", strip_tags(ob_get_clean(), "<tr><td><h2>"));
    $cat = "General";
    foreach ($info_lines as $line) {
        preg_match("~<h2>(.*)</h2>~", $line, $title) ? $cat = $title[1] : null;
        if (preg_match("~<tr><td[^>]+>([^<]*)</td><td[^>]+>([^<]*)</td></tr>~", $line, $val)) {
            $info_arr[$cat][$val[1]] = $val[2];
        }
        elseif (preg_match("~<tr><td[^>]+>([^<]*)</td><td[^>]+>([^<]*)</td><td[^>]+>([^<]*)</td></tr>~", $line, $val)) {
            $info_arr[$cat][$val[1]] = array("local" => $val[2], "master" => $val[3]);
        }
    }
    ob_end_flush();
    return $info_arr;
}

/**
 * [sync_www description]
 * @param  [type] $array [description]
 * @return [type]        [description]
 */
function sync_www($array)
{
    $result = $array;
    $handle = opendir(WWW_DIR);
    $projectContents = '';
    while ($file = readdir($handle)) {
        if (is_dir("../".$file)) {
            $result = addProject($result, $file);
        }
    }
    closedir($handle);
    return $result;
}

/**
 * [sync_alias description]
 * @param  [type] $array [description]
 * @return [type]        [description]
 */
function sync_alias($array)
{
    $alias = clearAlias(glob(WAMP_PATH.'alias/*.conf'));
    $result = $array;
    foreach($alias as $projectName) {
        $result = addProject($result, $projectName);
    }
    return $result;
}

/**
 * [clearAlias description]
 * @param  [type] $alias [description]
 * @return [type]        [description]
 */
function clearAlias($alias)
{
    global $projectsListIgnore;
    $projects_list = array();
    foreach ($alias as &$project) {
        $projectName = explode('/', $project);
        $projectName = substr($projectName[count($projectName) - 1], 0, -1 * strlen('.conf'));
        if(!in_array($projectName, $projectsListIgnore)) {
            $projects_list[] = $projectName;
        }
    }
    return $projects_list;
}

/**
 * [getAliasUrl description]
 * @param  [type] $path [description]
 * @return [type]       [description]
 */
function getAliasUrl($path)
{
    $handle = @fopen($path, 'r');
    if ($handle) {
        while (($buffer = fgets($handle)) !== false) {
            if (preg_match('#alias (.*) ".*"#i', $buffer, $match)) {
                fclose($handle);
                return ($match[1]);
            }
        }
        fclose($handle);
    }
    return null;
}

/**
 * [addProject description]
 * @param [type] $result [description]
 * @param [type] $file   [description]
 */
function addProject($result, $file) {
    global $projectsListIgnore;
    if ((!isset($array["projects"]["w-".slugify($file)]))&& !in_array(slugify($file), $projectsListIgnore)) {
        $result["projects"]["w-".slugify($file)] = array(
            'Alias'         => slugify($file),
            "Color"         => "success",
            "Image"         => "0",
            "Badges"        => [],
            "DEV"           => "",
            "PREPROD"       => "",
            "PROD"          => "",
            "Description"   => "",
            "Favorites"     => []
        );
    }
    return $result;
}

/**
 * [slugify description]
 * @param  [type] $text [description]
 * @return [type]       [description]
 */
function slugify($text)
{
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    if (empty($text)) {
        return 'n-a';
    }
    return $text;
}

$json = @file_get_contents("config.json");

// Iinitialize JSON
if (!$json) {

    // Language initialization
    $langue = 'en';
    if (isset($_GET['lang'])) {
        $langue = $_GET['lang'];
    } elseif (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) AND preg_match("/^fr/", $_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $langue = 'fr';
    }

    // Default JSON
    $default_json = array(
        'index' => true,
        'langue' => $langue,
        'favorites' => array(),
        'projects' => array()
    );
    $default_json = sync_www($default_json);
    updateConfig($default_json, true);

}

$json = file_get_contents("config.json");
$data = json_decode($json, true);
$langue = $data['langue'];
$table = phpinfo_array();

if (isset($_GET["add"])) {
    $generated = "Rand_index_".count($data["projects"]);
     if (!isset($data["projects"][$generated])) {
        $data["projects"][$generated] = array(
            'Alias' => $generated,
            "Color" => "warning",
            "Badges" => array(),
            "Favorites" => array()
        );
    }
}
if (isset($_GET["sync_www"])) {
    $data = sync_www($data);
}
if (isset($_GET["sync_alias"])) {
    $data = sync_alias($data);
}

// Coming soon
// if (isset($_GET["sync_vhost"])) {
    // sync_vhost($default_json);
// }

// Launch saveConfiguration function
if ((isset($_POST))&&($_POST!=array()))  saveConfiguration();

/**
 * [saveConfiguration description]
 * @return [type] [description]
 */
function saveConfiguration()
{
    global $data;
    $data["favorites"] = $_POST["favorites"];
    $data["projects"] = $_POST["projects"];
    foreach ($data["projects"] as $project_key => $project) {
        if (!isset($project["Alias"]) || $project["Alias"] == "") {
            unset($data["projects"][$project_key]);
        }
    }
    updateConfig($data);
    header("Refresh:0");
}

/**
 * Try to echo a variable
 * @param [type] $value   [description]
 * @param [type] $default [description]
 */
function echoIfIsset(&$value, $default = null)
{
    echo (isset($value)&&($value!='')) ? $value : $default;
}

/**
 * [compareIfIsset description]
 * @param [type] $value        [description]
 * @param [type] $compareTo    [description]
 * @param [type] $returnIfTrue [description]
 */
function compareIfIsset(&$value, $compareTo, $returnIfTrue)
{
    echo (isset($value)&&($value!='')) ? (($value==$compareTo) ? $returnIfTrue : null) : $default;
}

/**
 * Try to translate a string via the $messages file in the $langue language
 * @param  [type] $string [description]
 * @return [type]         [description]
 */
function e_($string)
{
    global $langue, $messages;
    $result = $string;
    if(@isset($messages[$langue][$string])){
        $result = $messages[$langue][$string];
    }
    echo $result;
}

/**
 * Update the config.json file
 * @param  [type] $json [description]
 * @return [type]       [description]
 */
function updateConfig($json, $create = false)
{
    if ((is_writable("config.json"))||($create)) {
        if (!$handle = fopen("config.json", 'w')) {
             e_("Impossible d'ouvrir le fichier de configuration");
             exit;
        }
        if (fwrite($handle, json_encode( $json ,JSON_UNESCAPED_UNICODE)) === FALSE) {
           e_("Impossible d'écrire dans le fichier de configuration");
           exit;
       }
       fclose($handle);
   } else {
       e_("Le fichier de configuration n'est pas accessible en écriture.");
   }
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta name="description" content="Launch fastly your projects with Dashboard Apache MySQL PHP">
        <meta name="author" content="Tom Jamon">
        <link rel="icon" href="../../favicon.ico">
        <title>DAMP</title>
        <link href="assets/css/bootstrap.min.css" rel="stylesheet">
        <link href="assets/css/style.min.css" rel="stylesheet">
    </head>

    <body>
        <?php preg_match("([0-9\.]+)", apache_get_version(), $match); ?>
        <?php $apache_version = $match[0]; ?>
        <?php $php_version = phpversion(); ?>

        <div class="collapse bg-inverse" id="navbarHeader">
            <div class="container">
                <div class="row">
                    <div class="col-sm-8 py-4">
                        <h4 class="text-white">About</h4>
                        <p class="text-muted">
                            <b>OS :</b> GNU/Linux, Windows, MacOS <br>
                            <b>Requirements :</b> Apache, PHP <br>
                            <b>Lib :</b> jQuery@3.1.1, Bootstrap@v4-alpha, Tether@1.3.3 <br>
                            <b>Fork me on Github :</b> https://github.com/TiDJ/DAMP
                        </p>
                    </div>
                    <div class="col-sm-4 py-4">
                        <h4 class="text-white">Favorites</h4>
                        <ul class="list-unstyled">
                            <li><a href="<?php echoIfIsset($data["favorites"][0]['link']); ?>" target="_blank" class="text-muted"><?php echoIfIsset($data["favorites"][0]['title']); ?></a></li>
                            <li><a href="<?php echoIfIsset($data["favorites"][1]['link']); ?>" target="_blank" class="text-muted"><?php echoIfIsset($data["favorites"][1]['title']); ?></a></li>
                            <li><a href="<?php echoIfIsset($data["favorites"][2]['link']); ?>" target="_blank" class="text-muted"><?php echoIfIsset($data["favorites"][2]['title']); ?></a></li>
                            <li><a href="<?php echoIfIsset($data["favorites"][3]['link']); ?>" target="_blank" class="text-muted"><?php echoIfIsset($data["favorites"][3]['title']); ?></a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="navbar navbar-inverse bg-inverse">
            <div class="container d-flex justify-content-between">
                <a href="index.php" class="navbar-brand">
                    <span class="hidden-xs-down"><i>Dashboard Apache MySQL PHP</i></span>
                    <span class="hidden-sm-up">DAMP</span>
                </a>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarHeader" aria-controls="navbarHeader" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>
        </div>

        <section style="background-color:white;" class="jumbotron text-center">
            <div class="container">
                <h1 style="font-size: 5rem;" class="jumbotron-heading">DAMP</h1>
                <p class="lead text-muted"><?php e_("Launch fastly your projects with Dashboard Apache Mysql PHP") ?></p>
                <p>
                    <a href="#projects" class="btn btn-secondary"><?php e_("Projects"); ?></a>
                    <a href="#accordion" class="btn btn-secondary"><?php e_("Configuration"); ?></a>
                    <a href="#parameters" class="btn btn-secondary"><?php e_("Customization"); ?></a>
                    <a href="#accordion" id="full_config" class="btn btn-secondary"><?php e_("Show full configuration"); ?></a>
                </p>
                <h6>
                    <i>
                        <?php e_("I got") ?> <span class="badge badge-primary">PHP <?php echo $php_version; ?></span>
                        <?php e_("with") ?> <span class="badge badge-warning">MySQL <?php echo $mysql_version; ?></span>
                        <?php e_("on") ?> <span class="badge badge-danger">Apache <?php echo $apache_version; ?></span>
                    </i>
                </h6>
                <p>
                    [<a href="?sync_www" class="btn btn-sm btn-grey">Sync WWW</a>]
                    [<a href="?sync_alias" class="btn btn-sm btn-grey">Sync ALIAS</a>]
                </p>
            </div>
        </section>

        <div id="projects">
            <div class="container">
                <div class="card-columns">
                    <?php foreach($data['projects'] as $projects_name => $projects_element){ ?>
                        <div class="card">
                            <?php if((isset($projects_element['Image']))&&($projects_element['Image'])): ?>
                                <img class="card-img-top" style="width: 100%; display: block;" src="thumbnails/<?php echo slugify($projects_element['Alias']) ?>.jpg" alt="<?php echo slugify($projects_element['Alias']) ?>">
                            <?php endif; ?>
                            <div class="card-block card-header-title card-default text-center" style="color:white;">
                                <h3 class="card-title"><?php echoIfIsset($projects_element['Alias'], $projects_name); ?></h3>
                                <p class="card-text"><?php echoIfIsset($projects_element['Description']); ?></p>
                                <?php if(!empty($projects_element['DEV'])): ?>
                                    <a target="_blank" data-toggle="tooltip" data-placement="top" title="<?php echoIfIsset($projects_element['DEV']); ?>" href="<?php echoIfIsset($projects_element['DEV']); ?>" class="btn btn-sm btn-<?php echoIfIsset($projects_element['Color'], "default"); ?>">DEV</a>
                                <?php endif; ?>
                                <?php if(!empty($projects_element['PREPROD'])): ?>
                                    <a target="_blank" data-toggle="tooltip" data-placement="top" title="<?php echoIfIsset($projects_element['PREPROD']); ?>" href="<?php echoIfIsset($projects_element['PREPROD']); ?>" class="btn btn-sm btn-<?php echoIfIsset($projects_element['Color'], "default"); ?>">PREPROD</a>
                                <?php endif; ?>
                                <?php if(!empty($projects_element['PROD'])): ?>
                                    <a target="_blank" data-toggle="tooltip" data-placement="top" title="<?php echoIfIsset($projects_element['PROD']); ?>" href="<?php echoIfIsset($projects_element['PROD']); ?>" class="btn btn-sm btn-<?php echoIfIsset($projects_element['Color'], "default"); ?>">PROD</a>
                                <?php endif; ?>
                            </div>
                            <?php $array_testing = array_filter($projects_element['Badges']); ?>
                            <?php $favorites = $projects_element['Favorites'];
                            $isEmptyFavorites = array();
                            foreach ($favorites as $favorite) {
                                $isEmptyFavorites[] = array_filter($favorite);
                            }
                            $isEmptyFavorites = array_filter($isEmptyFavorites);
                            ?>
                            <?php if((!empty($array_testing))||(!empty($isEmptyFavorites ))): ?>
                                <div class="card-block text-center">
                                    <?php if(!empty($array_testing)): ?>
                                        <span class="badge badge-pill badge-default"><?php echoIfIsset($projects_element['Badges'][0]); ?></span>
                                        <span class="badge badge-pill badge-default"><?php echoIfIsset($projects_element['Badges'][1]); ?></span>
                                        <span class="badge badge-pill badge-default"><?php echoIfIsset($projects_element['Badges'][2]); ?></span>
                                        <span class="badge badge-pill badge-default"><?php echoIfIsset($projects_element['Badges'][3]); ?></span>
                                        <span class="badge badge-pill badge-default"><?php echoIfIsset($projects_element['Badges'][4]); ?></span>
                                    <?php endif; ?>
                                    <?php foreach($projects_element['Favorites'] as $favorite): ?>
                                        <?php if($favorite['title'] != ""): ?>
                                            <span class="badge badge-pill badge-link">
                                                <a data-toggle="tooltip" data-placement="bottom" title="<?php echoIfIsset($favorite['link']); ?>" target="_blank" href="<?php echoIfIsset($favorite['link']); ?>">
                                                    <?php echoIfIsset($favorite['title']); ?>
                                                </a>
                                            </span>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>

        <?php if(SHOW_CONFIG): ?>
            <div class="container main-part">
                <div id="accordion" class="accordion" role="tablist" aria-multiselectable="true">
                    <div class="card">
                        <div class="card-header" role="tab" id="headingThree">
                            <a class="collapsed" data-toggle="collapse" data-parent="#accordion" href="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                PHP
                            </a>
                        </div>
                        <div id="collapseThree" class="collapse" role="tabpanel" aria-labelledby="headingThree">
                            <div class="card-block">
                                <div id="accordion2" class="accordion" role="tablist" aria-multiselectable="true">
                                    <div class="card">
                                        <div class="card-header" role="tab" id="phphextensions">
                                            <a class="collapsed" data-toggle="collapse" data-parent="#php_accordion" href="#phpcextensions" aria-expanded="false" aria-controls="phpcextensions">
                                                PHP Extensions
                                            </a>
                                        </div>
                                        <div id="phpcextensions" class="collapse" role="tabpanel" aria-labelledby="phphextensions">
                                            <div class="card-block">
                                                <?php foreach (get_loaded_extensions() as $extension): ?>
                                                    <span class="badge badge-pill badge-default"><?php echo $extension; ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php foreach ($table as $key => $value) { ?>
                                    <div class="card">
                                        <div class="card-header" role="tab" id="phph<?php echo slugify($key); ?>">
                                            <a class="collapsed" data-toggle="collapse" data-parent="#php_accordion" href="#phpc<?php echo slugify($key); ?>" aria-expanded="false" aria-controls="phpc<?php echo slugify($key); ?>">
                                                <?php echo $key ?>
                                            </a>
                                        </div>
                                        <div id="phpc<?php echo slugify($key); ?>" class="collapse" role="tabpanel" aria-labelledby="phph<?php echo slugify($key); ?>">
                                            <div class="card-block">
                                                <table class="table table-sm">
                                                    <thead class="thead-default">
                                                        <tr>
                                                            <th class="text-right">Key</th>
                                                            <th>Value</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($value as $key => $value) { ?>
                                                            <?php if(!is_array($value)){ ?>
                                                                <tr>
                                                                    <th class="text-right"><?php echo $key ?></th>
                                                                    <td><?php echo $value ?></td>
                                                                </tr>
                                                            <?php } else { ?>
                                                                <tr>
                                                                    <th class="text-right"><?php echo $key; ?></th>
                                                                    <td>
                                                                        <?php foreach ($value as $key => $value) { ?>
                                                                            <strong><?php echo $key; ?></strong> <?php echo $value; ?>
                                                                        <?php } ?>
                                                                    </td>
                                                            <?php } ?>
                                                        <?php } ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header" role="tab" id="headingTwo">
                            <a class="collapsed" data-toggle="collapse" data-parent="#accordion" href="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                MySQL
                            </a>
                        </div>
                        <div id="collapseTwo" class="collapse" role="tabpanel" aria-labelledby="headingTwo">
                            <div class="card-block">
                                <table class="table table-sm">
                                    <thead class="thead-default">
                                        <tr>
                                            <th>Key</th>
                                            <th>Value</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr><th scope="row">Host Info</th><td><?php echo mysql_get_host_info(); ?></td></tr>
                                        <tr><th scope="row">Client Info</th><td><?php echo mysql_get_client_info(); ?></td></tr>
                                        <tr><th scope="row">Proto Info</th><td><?php echo mysql_get_proto_info(); ?></td></tr>
                                        <tr><th scope="row">Server Info</th><td><?php echo mysql_get_server_info(); ?></td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if(SHOW_PARAMETERS): ?>
            <div class="main-part" style="background-color: #eceeef; ">
                <form action="./index.php" method="post">
                    <div class="container">
                        <div id="parameters" class="accordion" role="tablist" aria-multiselectable="true">
                            <div class="card">
                                <div class="card-header" role="tab" id="hp1">
                                    <a data-toggle="collapse" data-parent="#parameters" href="#c1" aria-expanded="false" aria-controls="c1">
                                        <?php e_('General settings'); ?>
                                    </a>
                                </div>
                                <div id="c1" class="collapse" role="tabpanel" aria-labelledby="hp1">
                                    <div class="card-block">
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-2 col-form-label"><?php e_("First favorite"); ?></label>
                                            <div class="col-md-5"><input type="text" value="<?php echoIfIsset($data["favorites"][0]['title']); ?>" name="favorites[0][title]" class="form-control" placeholder="Titles of favorites"></div>
                                            <div class="col-md-5"><input type="text" value="<?php echoIfIsset($data["favorites"][0]['link']); ?>" name="favorites[0][link]" class="form-control" placeholder="Link of favorites"></div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-2 col-form-label"><?php e_("Second favorite"); ?></label>
                                            <div class="col-md-5"><input type="text" value="<?php echoIfIsset($data["favorites"][1]['title']); ?>" name="favorites[1][title]" class="form-control" placeholder="Titles of favorites"></div>
                                            <div class="col-md-5"><input type="text" value="<?php echoIfIsset($data["favorites"][1]['link']); ?>" name="favorites[1][link]" class="form-control" placeholder="Link of favorites"></div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-2 col-form-label"><?php e_("Third favorite"); ?></label>
                                            <div class="col-md-5"><input type="text" value="<?php echoIfIsset($data["favorites"][2]['title']); ?>" name="favorites[2][title]" class="form-control" placeholder="Titles of favorites"></div>
                                            <div class="col-md-5"><input type="text" value="<?php echoIfIsset($data["favorites"][2]['link']); ?>" name="favorites[2][link]" class="form-control" placeholder="Link of favorites"></div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="example-text-input" class="col-2 col-form-label"><?php e_("Fourth favorite"); ?></label>
                                            <div class="col-md-5"><input type="text" value="<?php echoIfIsset($data["favorites"][3]['title']); ?>" name="favorites[3][title]" class="form-control" placeholder="Titles of favorites"></div>
                                            <div class="col-md-5"><input type="text" value="<?php echoIfIsset($data["favorites"][3]['link']); ?>" name="favorites[3][link]" class="form-control" placeholder="Link of favorites"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="sortableParameters">
                                <?php foreach($data['projects'] as $projects_name => $projects_element){ ?>
                                    <div class="card">
                                        <div class="card-header" role="tab" id="hp<?php echo $projects_name ?>">
                                            <a class="collapsed" data-toggle="collapse" data-parent="#parameters" href="#c<?php echo $projects_name ?>" aria-expanded="false" aria-controls="c<?php echo $projects_name ?>">
                                                [PROJECTS] <?php echoIfIsset($projects_element['Alias'], $projects_name); ?>
                                            </a>
                                        </div>
                                        <div id="c<?php echo $projects_name ?>" class="collapse " role="tabpanel" aria-labelledby="hp<?php echo $projects_name ?>">
                                            <div class="card-block">
                                                <div class="form-group row">
                                                    <label for="example-text-input" class="col-2 col-form-label"><?php e_("Project's name") ?></label>
                                                    <div class="col-md-4">
                                                        <input type="text" value="<?php echoIfIsset($projects_element['Alias']); ?>" name="projects[<?php echo $projects_name ?>][Alias]" class="form-control" placeholder="Name, deleted if empty">
                                                    </div>
                                                    <div class="col-md-3">
                                                        <select class="form-control" name="projects[<?php echo $projects_name ?>][Color]">
                                                            <option <?php compareIfIsset($projects_element['Color'], "default", "selected") ?> value="default"><?php e_("Default"); ?></option>
                                                            <option <?php compareIfIsset($projects_element['Color'], "success", "selected") ?> value="success"><?php e_("Finished"); ?></option>
                                                            <option <?php compareIfIsset($projects_element['Color'], "primary", "selected") ?> value="primary"><?php e_("Personnal"); ?></option>
                                                            <option <?php compareIfIsset($projects_element['Color'], "danger", "selected") ?> value="danger"><?php e_("Professional"); ?></option>
                                                            <option <?php compareIfIsset($projects_element['Color'], "warning", "selected") ?> value="warning"><?php e_("Others"); ?></option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <select class="form-control" name="projects[<?php echo $projects_name ?>][Image]">
                                                            <option <?php compareIfIsset($projects_element['Image'], true, "selected") ?> value="1">Image</option>
                                                            <option <?php compareIfIsset($projects_element['Image'], false, "selected") ?> value="0">No image</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="form-group row">
                                                    <label for="example-text-input" class="col-2 col-form-label"><?php e_("Project's badges") ?></label>
                                                    <div class="col-md-2">
                                                        <input type="text" value="<?php echoIfIsset($projects_element['Badges'][0]); ?>" name="projects[<?php echo $projects_name ?>][Badges][0]" class="form-control" placeholder="Badge 1">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <input type="text" value="<?php echoIfIsset($projects_element['Badges'][1]); ?>" name="projects[<?php echo $projects_name ?>][Badges][1]" class="form-control" placeholder="Badge 2">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <input type="text" value="<?php echoIfIsset($projects_element['Badges'][2]); ?>" name="projects[<?php echo $projects_name ?>][Badges][2]" class="form-control" placeholder="Badge 3">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <input type="text" value="<?php echoIfIsset($projects_element['Badges'][3]); ?>" name="projects[<?php echo $projects_name ?>][Badges][3]" class="form-control" placeholder="Badge 4">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <input type="text" value="<?php echoIfIsset($projects_element['Badges'][4]); ?>" name="projects[<?php echo $projects_name ?>][Badges][4]" class="form-control" placeholder="Badge 5">
                                                    </div>
                                                </div>
                                                <div class="form-group row">
                                                    <label for="example-text-input" class="col-2 col-form-label"><?php e_("Project's URL") ?></label>
                                                    <div class="col-md-3"><input type="text" value="<?php echoIfIsset($projects_element['DEV']); ?>" name="projects[<?php echo $projects_name ?>][DEV]" class="form-control" placeholder="DEV"></div>
                                                    <div class="col-md-4"><input type="text" value="<?php echoIfIsset($projects_element['PREPROD']); ?>" name="projects[<?php echo $projects_name ?>][PREPROD]" class="form-control" placeholder="PREPROD"></div>
                                                    <div class="col-md-3"><input type="text" value="<?php echoIfIsset($projects_element['PROD']); ?>" name="projects[<?php echo $projects_name ?>][PROD]" class="form-control" placeholder="PROD"></div>
                                                </div>
                                                <div class="form-group row">
                                                    <label for="example-text-input" class="col-2 col-form-label"><?php e_("Description"); ?></label>
                                                    <div class="col-md-10">
                                                        <textarea name="projects[<?php echo $projects_name ?>][Description]" class="form-control" placeholder="Description" rows="3"><?php echoIfIsset($projects_element['Description']); ?></textarea>
                                                    </div>
                                                </div>
                                                <div class="form-group row">
                                                    <label for="example-text-input" class="col-2 col-form-label"><?php e_("First link"); ?></label>
                                                    <div class="col-md-2"><input value="<?php echoIfIsset($projects_element['Favorites'][0]['title']); ?>" name="projects[<?php echo $projects_name ?>][Favorites][0][title]" type="text" class="form-control" placeholder="Intitulé"></div>
                                                    <div class="col-md-2"><input value="<?php echoIfIsset($projects_element['Favorites'][0]['link']); ?>" name="projects[<?php echo $projects_name ?>][Favorites][0][link]" type="text" class="form-control" placeholder="Lien"></div>
                                                    <label for="example-text-input" class="col-2 col-form-label"><?php e_("Second link"); ?></label>
                                                    <div class="col-md-2"><input value="<?php echoIfIsset($projects_element['Favorites'][1]['title']); ?>" name="projects[<?php echo $projects_name ?>][Favorites][1][title]" type="text" class="form-control" placeholder="Intitulé"></div>
                                                    <div class="col-md-2"><input value="<?php echoIfIsset($projects_element['Favorites'][1]['link']); ?>" name="projects[<?php echo $projects_name ?>][Favorites][1][link]" type="text" class="form-control" placeholder="Lien"></div>
                                                </div>
                                                <div class="form-group row">
                                                    <label for="example-text-input" class="col-2 col-form-label"><?php e_("Third link"); ?></label>
                                                    <div class="col-md-2"><input value="<?php echoIfIsset($projects_element['Favorites'][2]['title']); ?>" name="projects[<?php echo $projects_name ?>][Favorites][2][title]" type="text" class="form-control" placeholder="Intitulé"></div>
                                                    <div class="col-md-2"><input value="<?php echoIfIsset($projects_element['Favorites'][2]['link']); ?>" name="projects[<?php echo $projects_name ?>][Favorites][2][link]" type="text" class="form-control" placeholder="Lien"></div>
                                                    <label for="example-text-input" class="col-2 col-form-label"><?php e_("Fourth link"); ?></label>
                                                    <div class="col-md-2"><input value="<?php echoIfIsset($projects_element['Favorites'][3]['title']); ?>" name="projects[<?php echo $projects_name ?>][Favorites][3][title]" type="text" class="form-control" placeholder="Intitulé"></div>
                                                    <div class="col-md-2"><input value="<?php echoIfIsset($projects_element['Favorites'][3]['link']); ?>" name="projects[<?php echo $projects_name ?>][Favorites][3][link]" type="text" class="form-control" placeholder="Lien"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                            <div class="row">
                                <div class="col-sm-3">
                                    <a href="?add"  id="AddProject" name="" value="" class="btn btn-block">- <?php e_("Add manually"); ?> -</a>
                                </div>
                                <div class="col-sm-9">
                                    <input type="submit" name="" value="<?php e_('Save'); ?>" class="btn btn-success btn-block">
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <footer class="text-muted">
            <div class="container">
                <p class="float-right">
                    <a href="#"><?php e_("Back to top") ?></a>
                </p>
                <p><a target="_blank" href="http://v4-alpha.getbootstrap.com">Bootstrap</a> - <a target="_blank" href="http://jquery.com/">jQuery</a> - <a target="_blank" href="https://jqueryui.com/">jQuery UI</a> - <a target="_blank" href="http://tether.io/">Tether</a></p>
            </div>
        </footer>

        <script src="assets/js/jquery.min.js"></script>
        <script src="assets/js/jquery-ui.min.js"></script>
        <script src="assets/js/tether.min.js"></script>
        <script src="assets/js/app.min.js"></script>
        <script src="assets/js/bootstrap.min.js"></script>
    </body>
</html>
