<?php
// All of this code is copyrighted by Ivo Fokkema <Ivo@IFokkema.nl>,
//  and developed and written for his private website epoxytips.nl.
// This code has been licensed to the Leiden University Medical Center
//  for use on retina.lovd.nl.

function tips_displayError ($sTitle, $sError)
{
    // Display error.
    print('
        <div class="card border-danger mb-3">
          <div class="card-header">' . $sTitle . '</div>
          <div class="card-body text-danger">
            <p class="card-text">' . $sError . '</p>
          </div>
        </div>');
    return true;
}





function tips_formatPost ($aRequest)
{
    global $_DATA;
    // Formats a post.

    // Prepare body, replacing Markdown link format to HTML links.
    $sBody = implode("\n                ",
        array_map(
            function ($sLine) {
                if (preg_match('/\[([^\]]+)\]\(([^\)]+)\)/', $sLine, $aRegs)) {
                    // Link found.
                    // This code works only for the first link on this line.
                    list($sPattern, $sTitle, $sURL) = $aRegs;
                    $bInternal = (substr($sURL, 0, 4) != 'http'
                        && substr($sURL, 0, 2) != 'l/');
                    $sLine = str_replace(
                        $sPattern,
                        '<A href="' . $sURL . '"' . ($bInternal? '' : ' target="_blank"') . '>' .
                            $sTitle . '</A>',
                        $sLine
                    );
                }
                return $sLine;
            }, $aRequest['body']));
    $sCategories = '';
    $sTags = '';

    foreach ($aRequest['category'] as $sCategory) {
        $sCategories .= (!$sCategories? '' : ' > ') .
            '<a href="c/' . tips_getLink($sCategory) . '" class="card-link">' . ucfirst($sCategory) . '</a>';
    }
    foreach ($aRequest['tags'] as $sTag) {
        $sTags .= '<a href="t/' . tips_getLink($sTag) . '" class="card-link">#' . tips_getLink($sTag) . '</a>';
    }

    $sPost = '
        <div class="card shadow-sm">
            <div class="card-header">' .
        ((time() - strtotime($aRequest['date'])) > (7 * 24 * 60 * 60)? '' : '
                <span class="float-right badge badge-success">Recently updated!</span>') . '
                <small class="text-muted">' . $sCategories . '</small>
                <h4 class="card-title">' . $aRequest['title'] . '</h4>
            </div>
            <div class="card-body">
                ' . $sBody . '
            </div>
            <div class="card-footer">
                <div class="d-flex justify-content-between align-items-center">
                    <div>' .
        (!$sTags? '' : '
                        ' . $sTags) . '

                    </div>
                    <small>last updated ' . $aRequest['date'] . '</small>
                </div>' .
        (true || empty($aRequest['code'])? '' : '
                <span class="badge badge-primary">https://epoxytips.nl/' . $aRequest['code'] . '</span>') . '
            </div>
        </div>';

    if (!empty($aRequest['related'])) {
        $sRelatedLinks = '';
        foreach ($aRequest['related'] as $sRelated) {
            // Fetch last updated date from target page.
            $sDate = '1970-01-01';
            foreach ($_DATA['tips'] as $aTip) {
                if ($aTip['title'] == $sRelated) {
                    $sDate = $aTip['date'];
                    break;
                }
            }
            $sRelatedLinks .= '
                <li class="list-group-item">' .
                ((time() - strtotime($sDate)) > (7 * 24 * 60 * 60)? '' : '
                    <span class="float-right badge badge-success">Recently updated!</span>') . '
                    <a href="' . tips_getLink($sRelated) . '">' . $sRelated . '</a></li>';
        }
        $sPost .= '
        <br><br>
        <div class="card border-primary">
            <div class="card-header">
                Related links
            </div>
            <ul class="list-group list-group-flush">' .
            $sRelatedLinks . '
            </ul>
        </div>';
    }

    return $sPost;
}





function tips_getCodes ()
{
    // Get all codes from the tips and return them.
    global $_DATA;
    static $aCodes = array();

    if (empty($aCodes)) {
        foreach ($_DATA['tips'] as $aTip) {
            if (!empty($aTip['code'])) {
                $aCodes[$aTip['code']] = tips_getLink($aTip['title']);
            }
        }
    }

    return $aCodes;
}





function tips_getTitles ()
{
    // Get all titles from the tips and return them with their tips attached.
    global $_DATA;
    static $aTitles = array();

    if (empty($aTitles)) {
        foreach ($_DATA['tips'] as $aTip) {
            // Deliberately overwrite title if we already have it.
            // I should not have created two of the same pages.
            // I might as well just take the entire database here.
            $aTitles[tips_getLink($aTip['title'])] = $aTip;
        }
    }

    return $aTitles;
}





function tips_getInstallURL ($bFull = true)
{
    // Returns URL that can be used in URLs or redirects.
    // ROOT_PATH can be relative or absolute.
    return (!$bFull? '' : PROTOCOL . $_SERVER['HTTP_HOST']) .
        str_replace(array(
            '/./',
            '//'
        ), array(
            '/',
            '/'
        ), substr(ROOT_PATH, 0, 1) == '/'? ROOT_PATH : dirname($_SERVER['SCRIPT_NAME']) . '/' . ROOT_PATH);
}





function tips_getLink ($sTitle)
{
    // Makes a link from a title.
    static $aLinks = array();

    if (!isset($aLinks[$sTitle])) {
        $sLink = str_replace(array(
            ' ',
            ':',
        ), array(
            '-',
            '-',
        ), preg_replace(
            '/[^a-z0-9 :_-]/',
            '',
            // Get rid of accents on characters.
            iconv('UTF-8', 'ASCII//TRANSLIT', strtolower($sTitle))));
        $aLinks[$sTitle] = $sLink;
    }

    return $aLinks[$sTitle];
}





function tips_setOGData ($aData)
{
    // Sets the Open Graph data for this page.

    $sContents = ob_get_contents();
    foreach (array('title', 'description') as $sProperty) {
        if (isset($aData[$sProperty])) {
            // Currently allows only empty contents to be filled in.
            $sContents = str_replace(
                '"og:' . $sProperty . '" content=""',
                '"og:' . $sProperty . '" content="' . $aData[$sProperty] . '"',
                $sContents);
        }
    }

    // Replace the buffer.
    ob_clean();
    echo $sContents;
    return true;
}





function tips_showCategories ($sURL)
{
    // Show categories or category, with links to posts.
    global $_DATA;

    // URL starts with 'c/' or is just 'c'.
    $sCategory = strtolower(substr($sURL, 2));
    $bShowAll = (!$sCategory);

    if ($bShowAll) {
        // No category requested. Fetch list to get order right.
        $aCategories = array_combine(
            $_DATA['categories'],
            array_fill(0, count($_DATA['categories']), array()));
    } else {
        $aCategories = array();
    }

    foreach ($_DATA['tips'] as $aTip) {
        // FIXME: Currently just allowing one-level categories.
        if ($bShowAll || $sCategory == $aTip['category'][0]) {
            $aCategories[$aTip['category'][0]][] = array($aTip['title'], $aTip['date']);
        }
    }

    if (!$aCategories) {
        // Wrong or non-existent category.
        tips_displayError('Unknown category', 'This category is unknown. Go back to the home page or use the search form to find what you\'re looking for.');
    }

    // Fill in OG data to make the Facebook previews pretty.
    tips_setOGData(array(
        'title' => ($bShowAll? 'All categories' : 'Category: ' . $sCategory),
    ));

    $sPost = '';
    foreach ($aCategories as $sCategory => $aTitles) {
        $sCategoryLinks = '';
        if (!$aTitles) {
            // Empty category. Just skip, that's easier.
            continue;
        }
        foreach ($aTitles as list($sTitle, $sDate)) {
            $sCategoryLinks .= '
            <li class="list-group-item">' .
                ((time() - strtotime($sDate)) > (7 * 24 * 60 * 60)? '' : '
                <span class="float-right badge badge-success">Recently updated!</span>') . '
                <a href="' . tips_getLink($sTitle) . '">' . $sTitle . '</a>
            </li>';
        }
        $sPost .= (!$sPost? '' : '
        <BR><BR>') . '
        <div class="card border-primary">
            <div class="card-header">
                ' . ucfirst($sCategory) . '
            </div>
            <ul class="list-group list-group-flush">' .
                $sCategoryLinks . '
            </ul>
        </div>';
    }

    echo $sPost;
}





// Addition specifically for retina.LOVD.nl.
function tips_showGenes ($sURL)
{
    // Show list of genes that we're working on.
    global $_DATA;

    // URL starts with 'genes/' or is just 'genes'.
    $sGenePrefix = strtolower(substr($sURL, 6));
    $aResults = array();

    foreach ($_DATA['genes'] as $sGene) {
        // If we're searching, then select only the genes matching the prefix.
        // Otherwise, show all genes.
        if (!$sGenePrefix || strpos(strtolower($sGene), $sGenePrefix) === 0) {
            $aResults[] = array(
                'title' => $sGene,
                'date' => '2020-01-01', // In case later we'll show when we updated data.
                'progress' => '', // In case we'll later show how far we are.
            );
        }
    }

    if (!$aResults) {
        // No results.
        tips_displayError('No results', 'There are no results for your search term. Try a different term, or <a href="https://www.LOVD.nl/contact?other" target="_blank">contact us</A>.');
        return false;
    }

    // Fill in OG data to make the Facebook previews pretty.
    tips_setOGData(array(
        'title' => (!$sGenePrefix? 'List of genes we are working on' : 'Search results'),
    ));

    print('
        <div class="card border-primary">
            <div class="card-header">
                ' . (!$sGenePrefix? 'List of genes we are working on' : 'Search results') . '
            </div>
            <ul class="list-group list-group-flush">');
    foreach ($aResults as $aGene) {
        print('
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <a href="https://LOVD.nl/' . $aGene['title'] . '" target="_blank">' . $aGene['title'] . '</a><span>' .
            ((time() - strtotime($aGene['date'])) > (7 * 24 * 60 * 60)? '' : '
                    <span class="badge badge-success">Recently updated!</span>') . '
                    <span class="badge badge-primary badge-pill">' . $aGene['progress'] . '</span></span>
                </li>');
    }
    print('
            </ul>
        </div>');
    return true;
}





function tips_showLinks ($sURL)
{
    // Show links or forward to target page.
    global $_DATA;

    // URL starts with 'l/' or is just 'l'.
    $sLink = strtolower(substr($sURL, 2));
    $bShowAll = (!$sLink);

    if ($bShowAll) {
        // No specific link requested. Fetch list and display.
        $aLinks = $_DATA['links'];
    } else {
        $aLinks = array();
        foreach ($_DATA['links'] as $sKey => $sURL) {
            if ($sKey == $sLink) {
                // Full match. Take only this one.
                $aLinks = array($sKey => $sURL);
                break;
            } elseif (substr($sKey, 0, strlen($sLink)) == $sLink) {
                // Prefix match. Add it to list.
                $aLinks[$sKey] = $sURL;
            }
        }
    }

    // FIXME: You could try and first match using similarity scores.
    if (!$aLinks) {
        // Wrong or non-existent link.
        // Log this.
        $sReferrer = (!isset($_SERVER['HTTP_REFERER'])? '' : $_SERVER['HTTP_REFERER']);
        // @file_put_contents(
        //     ROOT_PATH . 'logs/' . date('Y-m') . '.log',
        //     date('Y-m-d\TH:i:s') . "\t" . $_SERVER['REMOTE_ADDR'] . "\t" . $_SERVER['REQUEST_URI'] . "\t" . $sReferrer . "\t" . 'Error: Link not found.' . "\n",
        //     FILE_APPEND|LOCK_EX
        // );

        tips_displayError('Unknown link', 'This link is unknown. If you have followed a link from our page, then this is an error on our side.');
        return false;
    }

    // Fill in OG data to make the Facebook previews pretty.
    tips_setOGData(array(
        'title' => ($bShowAll? 'All links' : 'Link: ' . $sLink),
    ));

    if (count($aLinks) == 1) {
        // Just one link, go for it.
        header('Location: ' . current($aLinks), true, 302);
        exit;
    }

    $sPost = '
        <div class="card border-primary">
            <div class="card-header">
                Links matching the request
            </div>
            <ul class="list-group list-group-flush">';
    foreach ($aLinks as $sKey => $sURL) {
        $sPost .= '
            <li class="list-group-item">
                <a href="l/' . $sKey . '">' . $sKey . '</a>
            </li>';
    }
    $sPost .= '
            </ul>
        </div>';

    echo $sPost;
}





function tips_showPost ($aRequest)
{
    // Show a page.
    global $_DATA;

    if (!isset($aRequest['body'])) {
        // We don't have a body, so try and find the page.
        if (isset($aRequest['code'])) {
            // Find the page by te code.
            foreach ($_DATA['tips'] as $aTip) {
                if (isset($aTip['code']) && $aTip['code'] == $aRequest['code']) {
                    $aRequest = $aTip;
                    break;
                }
            }
        }
    }

    if (!isset($aRequest['body'])) {
        tips_displayError('Page not found', 'I can not find this page. Go back to the home page or use the search form to find what you\'re looking for.');
        return false;
    }

    // Fill in OG data to make the Facebook previews pretty.
    tips_setOGData(array(
        'title' => $aRequest['title'],
        'description' => rtrim(strip_tags($aRequest['body'][0]), '.,') . '...',
    ));

    print tips_formatPost($aRequest);
    return true;
}





function tips_showSearchResults ($sURL)
{
    // Show search results with links to posts.
    global $_DATA;

    // URL starts with 's/' or is just 's'.
    $sSearchTerms = strtolower(substr($sURL, 2));
    $aResults = array();

    if (!$sSearchTerms) {
        tips_displayError('No search term', 'You have not provided a search term. Fill in a search term in the field in the menu on the top of the page, and press &quot;Search&quot;.');
        return false;
    }

    foreach ($_DATA['tips'] as $aTip) {
        // Bonus points (x10) if the page's code matches,
        //  extra points (x5) if the subject matches,
        //  extra points (x2) if the page's tags match, otherwise
        //  just one point each time the body matches.
        $nScore = 0;
        foreach (explode(' ', $sSearchTerms) as $sSearchTerm) {
            $nScore += substr_count($aTip['code'], $sSearchTerm) * 10;
            $nScore += substr_count(strtolower($aTip['title']), $sSearchTerm) * 5;
            foreach ($aTip['tags'] as $sTag) {
                $nScore += substr_count(strtolower($sTag), $sSearchTerm) * 2;
            }
            foreach ($aTip['body'] as $sLine) {
                $nScore += substr_count(strtolower($sLine), $sSearchTerm);
            }
        }
        if ($nScore > 0) {
            $aTip['score'] = $nScore;
            $aResults[] = $aTip;
        }
    }
    usort($aResults, function ($aTip1, $aTip2) {
        return ($aTip1['score'] < $aTip2['score']? 1 :
            ($aTip1['score'] == $aTip2['score']? 0 : -1));
    });

    if (!$aResults) {
        // No results.
        tips_displayError('No results', 'There are no results for your search term. Try a different term, or <a href="https://www.LOVD.nl/contact?other" target="_blank">contact us</A>.');
        return false;
    }
    $nMaxScore = max(20, $aResults[0]['score']);

    // Fill in OG data to make the Facebook previews pretty.
    tips_setOGData(array(
        'title' => 'Search results',
    ));

    print('
        <div class="card border-primary">
            <div class="card-header">
                Results
            </div>
            <ul class="list-group list-group-flush">');
    foreach ($aResults as $aTip) {
        print('
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <a href="' . tips_getLink($aTip['title']) . '">' . $aTip['title'] . '</a><span>' .
            ((time() - strtotime($aTip['date'])) > (7 * 24 * 60 * 60)? '' : '
                    <span class="badge badge-success">Recently updated!</span>') . '
                    <span class="badge badge-primary badge-pill">' . round(($aTip['score']/$nMaxScore)*100) . '%</span></span>
                </li>');
    }
    print('
            </ul>
        </div>');
    return true;
}





function tips_showTags ($sURL)
{
    // Show tags or specific tag with links to posts.
    global $_DATA;

    // URL starts with 't/' or is just 't'.
    $sThisTag = strtolower(substr($sURL, 2));
    $bShowAll = (!$sThisTag);
    $aAllTags = array();
    $aThisTag = array();

    foreach ($_DATA['tips'] as $aTip) {
        foreach ($aTip['tags'] as $sTag) {
            if (!isset($aAllTags[$sTag])) {
                $aAllTags[$sTag] = 0;
            }
            $aAllTags[$sTag] ++;
            if (!$bShowAll && $sThisTag == $sTag) {
                $aThisTag[] = array($aTip['title'], $aTip['date']);
            }
        }
    }
    ksort($aAllTags);

    if (!$aAllTags) {
        // No tags exist anywhere.
        tips_displayError('No tags', 'No tags have been provided yet.');
        return false;
    }

    if (!$bShowAll) {
        if (!$aThisTag) {
            // Wrong or non-existent tag.
            tips_displayError('Unknown tag', 'This tag is unknown. Go back to the home page or use the search form to find what you\'re looking for.');
            return false;
        } else {
            $sTagLinks = '';
            foreach ($aThisTag as list($sTitle, $sDate)) {
                $sTagLinks .= '
            <li class="list-group-item">' .
                    ((time() - strtotime($sDate)) > (7 * 24 * 60 * 60)? '' : '
                <span class="float-right badge badge-success">Recently updated!</span>') . '
                <a href="' . tips_getLink($sTitle) . '">' . $sTitle . '</a>
            </li>';
            }
            echo '
        <div class="card border-primary">
            <div class="card-header">
                ' . ucfirst($sThisTag) . '
            </div>
            <ul class="list-group list-group-flush">' .
                $sTagLinks . '
            </ul>
        </div>
        <br><br>';
        }
    }

    // Fill in OG data to make the Facebook previews pretty.
    tips_setOGData(array(
        'title' => ($bShowAll? 'All tags' : 'Tag: ' . $sThisTag),
    ));

    print('
        <div class="card border-primary">
            <div class="card-header">
                Alle tags
            </div>
            <ul class="list-group list-group-flush">');
    foreach ($aAllTags as $sTag => $nCount) {
        print('
                <li class="list-group-item d-flex justify-content-between align-items-center"><a href="t/' . $sTag . '">' . $sTag . '</a><span class="badge badge-primary badge-pill">' . $nCount . '</span></li>');
    }
    print('
            </ul>
        </div>');
    return true;
}
?>
