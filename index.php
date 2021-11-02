<?php
// All of this code is copyrighted by Ivo Fokkema <Ivo@IFokkema.nl>,
//  and developed and written for his private website epoxytips.nl.
// This code has been licensed to the Leiden University Medical Center
//  for use on retina.lovd.nl.

setlocale(LC_CTYPE, 'en_US.utf8'); // needed for iconv.
define('ROOT_PATH', './');
require ROOT_PATH . 'inc-lib.php';

// Find out whether or not we're using SSL.
if ((!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') || !empty($_SERVER['SSL_PROTOCOL'])) {
    // We're using SSL!
    define('PROTOCOL', 'https://');
} else {
    define('PROTOCOL', 'http://');
}

// Open JSON file.
$sData = file_get_contents('data.json');
if ($sData) {
    $_DATA = json_decode($sData, true);
}
ob_start();
?>
<!doctype html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">

    <title>Retina.LOVD.nl</title>
    <BASE href="<?php echo tips_getInstallURL(); ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png">
    <link rel="shortcut icon" type="image/x-icon" href="favicon.ico">

    <meta property="og:title" content="">
    <meta property="og:image" content="<?php echo tips_getInstallURL(); ?>gfx/og_image.png">
    <meta property="og:image:width" content="200">
    <meta property="og:image:height" content="200">
    <meta property="og:description" content="">
    <meta property="og:locale" content="nl_NL">
    <meta property="og:site_name" content="Retina.LOVD.nl">

    <style type="text/css">
        a {color: rgb(0, 54, 96);}
        body {background-color: rgba(16, 190, 210, 0.2);}
        footer {background-color: rgba(0, 0, 0, 0.1);}
        .card  {background-color: rgba(255, 255, 255, 0.5);}
        .card-header {background-color: rgba(0, 54, 96, 0.5);}
        .card-footer {background-color: rgba(0, 54, 96, 0.5);}
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark" style="background: #003660;">
        <a class="navbar-brand font-weight-bold" href="">Retinal diseases genetic variants registry funded by Foundation Fighting Blindness</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav mr-auto">
                <li class="nav-item">
                    <a class="nav-link" href="funding">Funding</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="genes">Genes</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="l/donate" target="_blank">Donate to FFB</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="l/portal" target="_blank">Portal</a>
                </li>
            </ul>
            <form class="form-inline" onsubmit="document.location.href='<?php echo tips_getInstallURL(); ?>'+'s/'+document.forms[0].search.value; return false;">
                <div class="input-group">
                    <input id="search" type="text" class="form-control" placeholder="Search term(s)">
                    <div class="input-group-append">
                        <button type="submit" class="btn btn-outline-light">Search</button>
                    </div>
                </div>
            </form>
        </div>
    </nav>

    <div class="container my-4">

<?php
if (empty($_DATA)) {
    tips_displayError('Maintenance', 'This site is being maintained at the moment.');
} else {
    $sURL = preg_replace(
        '/^' . preg_quote(tips_getInstallURL(false), '/') . '/',
        '',
        strip_tags(rawurldecode($_SERVER['REQUEST_URI'])));
    $sURL = strstr($sURL . '?', '?', true);
    if (!$sURL) {
        // No URL given. Go to the "why" page.
        header('Location: ' . tips_getInstallURL() . 'home', 302);
        exit;
    }

    // Special pages.
    // Addition specifically for retina.LOVD.nl.
    elseif (substr($sURL . '/', 0, 6) == 'genes/') {
        // Gene page.
        tips_showGenes($sURL);
    }

    elseif (substr($sURL . '/', 0, 2) == 'c/') {
        // Categories.
        tips_showCategories($sURL);
    }

    elseif (substr($sURL . '/', 0, 2) == 'l/') {
        // Links.
        tips_showLinks($sURL);
    }

    elseif (substr($sURL . '/', 0, 2) == 's/') {
        // Searching.
        tips_showSearchResults($sURL);
    }

    elseif (substr($sURL . '/', 0, 2) == 't/') {
        // Tags.
        tips_showTags($sURL);

    } else {
        $aCodes = tips_getCodes();
        if (isset($aCodes[$sURL])) {
            tips_showPost(array('code' => $sURL));
        }

        // Otherwise, try to match with the titles (so I don't have to create codes).
        else {
            // Create a getTitles() and just sort through those,
            //  looking for the best match.
            $aTitles = tips_getTitles();
            if (isset($aTitles[$sURL])) {
                tips_showPost($aTitles[$sURL]);
            }

            // Nothing left to do, page not found.
            else {
                // Forward to a search.
                header('Location: ' . tips_getInstallURL() . 's/' . rawurlencode($sURL), 302);
                tips_displayError('Page not found', 'Page not found. Go back to the home page or use the search form to find what you\'re looking for.');
            }
        }
    }
}
?>
        <footer class="my-md-4 border-top px-lg-2" style="background: #003660;">
            <small class="text-white">&copy; 2020-2021 Leiden University Medical Center (LUMC)</small>
        </footer>

    </div>

    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>

</body>
</html>
<?php
ob_end_flush(); // Output everything.
?>
