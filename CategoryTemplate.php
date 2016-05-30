<?php

/**
 * MediaWiki CategoryTemplate extension
 *
 * @author Vitaliy Filippov <vitalif@mail.ru>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 *
 * This extension adds a textbox and a button to category pages, allowing to easily
 * create pages inside the category. After entering a title and pressing "Create",
 * you get one of the following:
 * 1) Redirect to Special:Upload with the "description" field pre-filled for File:... titles
 * 2) Redirect to Special:FormCreate/<Form>/<Title> if you're using Semantic Forms extension
 *    and a {{#default form:..}} is defined.
 * 3) Redirect to page editing with the textbox pre-filled in other cases.
 *
 * Pre-fill text is generated from Template:Category:CATEGORY_NAME if it exists.
 * __FULLPAGENAME__ inside it is replaced with the entered page title.
 * <!-- default title = ... --> is matched and removed from the template,
 * and used as a default value for the page creation textbox on the category page.
 * '$' character inside the default title means cursor position, i.e.
 * you can specify <!-- default title = Prefix $ (suffix) -->,
 * and the initial page title will be "Prefix  (suffix)" with cursor placed
 * between two spaces.
 *
 * Inspired by Liang Chen The BiGreat's extension:
 * http://www.liang-chen.com/myworld/content/view/36/70/
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 */

if (!defined('MEDIAWIKI'))
    die();

$wgHooks['CategoryPageView'][] = 'efCategoryTemplateCategoryPageView';
$wgHooks['ParserAfterParse'][] = 'efCategoryTemplateParserAfterParse';
$wgExtensionMessagesFiles['CategoryTemplate'] = dirname(__FILE__) . '/CategoryTemplate.i18n.php';
$wgExtensionCredits['other'][] = array (
    'name'        => 'Add Article to Category with template',
    'description' => 'Your MediaWiki will get an inputbox on each Category page, and you can create a new article directly to that category, based on Template:Category:<category_name> or simple [[Category:<category_name>]] string',
    'author'      => 'Vitaliy Filippov',
    'url'         => 'http://wiki.4intra.net/CategoryTemplate',
    'version'     => '1.2 (2015-11-25)',
);

$wgResourceModules['ext.CategoryTemplate'] = array(
    'localBasePath' => __DIR__,
    'remoteExtPath' => 'CategoryTemplate',
    'scripts' => array('CategoryTemplate.js'),
    'messages' => array('addcategorytemplate-confirm'),
);

function efCategoryTemplateParserAfterParse($parser, $text, $stripState)
{
    $output = $parser->getOutput();
    $sf = $output->getProperty('SFDefaultForm');
    if ($sf)
    {
        $output->addJsConfigVars(array(
            'sfDefaultForm' => $sf,
        ));
    }
}

function efCategoryTemplateCategoryPageView($catpage)
{
    global $wgOut, $wgScript, $wgTitle, $wgParser, $wgContLang, $wgCanonicalNamespaceNames, $CategoryTemplateMessages;
    if (!$wgTitle->quickUserCan('create'))
    {
        // Only if users have rights to create pages
        return true;
    }
    $boxtext = addslashes(wfMsg("addcategorytemplate-create-article"));
    $btext = addslashes(wfMsg("addcategorytemplate-submit"));
    $Action = htmlspecialchars($wgScript);
    $s1 = addslashes($wgCanonicalNamespaceNames[NS_FILE]);
    $s2 = addslashes($wgContLang->getNsText(NS_FILE));
    $cat = $catpage->mTitle->getText();
    $deftitle = $defpos = '';
    if (($title = Title::newFromText($wgContLang->getNsText(NS_TEMPLATE).":".$wgContLang->getNsText(NS_CATEGORY).":".$cat)) &&
        $title->userCan('read') &&
        ($rev = Revision::newFromId($title->getLatestRevID())))
    {
        /* Fetch page template from Template:Category:CATEGORY_NAME */
        $text = $rev->getText();
        if (preg_match('/<!--\s*default\s*title\s*=\s*(.*?)-->\s*/is', $text, $m, PREG_OFFSET_CAPTURE))
        {
            /* Match and remove <!-- default title = ... --> from template source.
               This value will be placed into the page title editbox by default.
               Optional '$' inside it indicates cursor position. */
            $deftitle = addslashes(trim($m[1][0]));
            if (($defpos = mb_strpos($deftitle, '$')) !== false)
                $deftitle = mb_substr($deftitle, 0, $defpos) . mb_substr($deftitle, $defpos+1);
            $text = substr($text, 0, $m[0][1]) . substr($text, $m[0][1]+strlen($m[0][0]));
        }
        $text = $wgParser->getPreloadText($text, $wgTitle, new ParserOptions());
    }
    else
    {
        /* Default page template: add only category membership */
        $text = "\n\n\n[[".$wgContLang->getNsText(NS_CATEGORY).":$cat]]";
    }
    $temp2 = <<<ENDFORM
<table border="0" align="right" width="423" cellspacing="0" cellpadding="0">
<tr><td width="100%" align="right" bgcolor="">
<form name="createbox" action="{$Action}" method="POST" class="createbox" onsubmit="return catTemplate.checkName()">
    <input type="hidden" name="action" id="createbox_action" value="edit" />
    <input type="hidden" name="redlink" value="1" />
    <input type="hidden" name="wpDestFile" value="" />
    <input type="hidden" name="wpUploadDescription" value="" />
    <input type="hidden" name="wpTextbox1" value="" />
    <input type="hidden" name="title" value="" />
    <input id="createboxInput" class="createboxInput" type="text"
        value="$boxtext" size="30" style="color: #666; font-size: 100%;" onfocus="catTemplate.clearText(this);" onblur="catTemplate.addText(this);" />
    <input type="submit" name="create" class="createboxButton" value="$btext" style="font-size: 100%;" />
</form>
</td></tr>
</table>
ENDFORM;
    $temp2 .= Skin::makeVariablesScript(array(
        'catTpl' => array(
            'ns_file' => "^($s1|$s2):",
            'text' => $text,
            'deftitle' => $deftitle,
            'deftitlepos' => $defpos,
        ),
    ));
    $wgOut->addHTML($temp2);
    $wgOut->addModules('ext.CategoryTemplate');
    return true;
}
