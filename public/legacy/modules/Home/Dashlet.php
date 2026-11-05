<?php
/**
 *
 * SugarCRM Community Edition is a customer relationship management program developed by
 * SugarCRM, Inc. Copyright (C) 2004-2013 SugarCRM Inc.
 *
 * SuiteCRM is an extension to SugarCRM Community Edition developed by SuiteCRM Ltd.
 * Copyright (C) 2011 - 2025 SuiteCRM Ltd.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY SUGARCRM, SUGARCRM DISCLAIMS THE WARRANTY
 * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along with
 * this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 *
 * You can contact SugarCRM, Inc. headquarters at 10050 North Wolfe Road,
 * SW2-130, Cupertino, CA 95014, USA. or at email address contact@sugarcrm.com.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "Powered by
 * SugarCRM" logo and "Supercharged by SuiteCRM" logo. If the display of the logos is not
 * reasonably feasible for technical reasons, the Appropriate Legal Notices must
 * display the words "Powered by SugarCRM" and "Supercharged by SuiteCRM".
 */
if (!defined('sugarEntry') || !sugarEntry) {
    die('Not A Valid Entry Point');
}

/**
 * One dashlet of the user's Home dashboard, alone, as the legacy dashboard shows it
 * (index.php?module=Home&action=Dashlet&id=<dashlet id>).
 *
 * The SuiteCRM 8 dashboard shows the dashlets it does not render itself (reports,
 * tools...) in a frame with this page. The dashlet keeps its own scripts: pages, sort
 * and charts work as on the legacy dashboard.
 *
 * With configure=1, the page opens the legacy options form of the dashlet (the options
 * modal of these dashlets in the SuiteCRM 8 dashboard), and tells the dashboard when the
 * form is saved or closed (dashlet-options message).
 */

global $current_user, $theme;

require_once 'include/MySugar/MySugar.php';

$id = (string)($_REQUEST['id'] ?? '');
$configure = !empty($_REQUEST['configure']);
$dashlets = $current_user->getPreference('dashlets', 'Home');
$definition = is_array($dashlets) ? ($dashlets[$id] ?? null) : null;

if ($definition === null || empty($definition['fileLocation']) || !is_file($definition['fileLocation'])) {
    echo '<p class="error">' . htmlspecialchars(translate('LBL_DASHBOARD_DASHLET_NOT_FOUND', 'Home'), ENT_QUOTES) . '</p>';

    return;
}

if (!is_file($cacheFile = sugar_cached('dashlets/dashlets.php'))) {
    require_once 'include/Dashlets/DashletCacheBuilder.php';
    (new DashletCacheBuilder())->buildCache();
}
$dashletsFiles = [];
require $cacheFile;

$module = $dashletsFiles[$definition['className']]['module'] ?? ($definition['module'] ?? '') ?: 'Home';
if (!(new MySugar($module))->checkDashletDisplay()) {
    return;
}

require_once $definition['fileLocation'];
$dashlet = new $definition['className']($id, $definition['options'] ?? []);

// The options form does not show the dashlet: it is not run (a report dashlet without
// report printed a PHP warning in the form).
$display = $script = '';
if (!$configure) {
    $dashlet->process($definition['sort_options'] ?? []);
    try {
        $display = $dashlet->display();
    } catch (Exception $exception) {
        $display = htmlspecialchars($exception->getMessage(), ENT_QUOTES);
    }
    $script = $dashlet->hasScript ? $dashlet->displayScript() : '';
}

// Chart and dashlet scripts of the legacy dashboard (modules/Home/index.php).
require_once 'include/SuiteGraphs/RGraphIncludes.php';
require_once 'include/SugarCharts/SugarChartFactory.php';
$sugarChart = SugarChartFactory::getInstance();

echo getVersionedScript('cache/include/javascript/sugar_grp_yui_widgets.js');
echo getVersionedScript('include/javascript/dashlets.js');
if ($sugarChart) {
    echo $sugarChart->getChartResources();
    echo $sugarChart->getMySugarChartResources();
}
?>
<style>
    /* The SuiteCRM 8 dashboard shows the title: only the dashlet content here. */
    body { background: #fff; padding: 0 !important; margin: 0 !important; }
    #pageContainer .dashletTable, #pageContainer .dashletcontainer { width: 100%; }
    #dashlet_entire_<?php echo $id; ?> > .dashletPanel-header,
    #dashlet_entire_<?php echo $id; ?> .dashlet-header,
    #dashlet_entire_<?php echo $id; ?> > .hd { display: none !important; }
<?php if ($configure) { ?>
    /* Options form only: the dialog fills the frame of the options modal. */
    .dashboard { display: none; }
    #dlg_c { position: static !important; width: 100% !important; }
    #dlg { width: 100% !important; left: 0 !important; top: 0 !important; box-shadow: none !important; }
    #dlg .hd, #dlg .container-close, .mask { display: none !important; }
    /* Labels on one line, wider fields (report name, charts). */
    #dlg table.edit.view td[scope='row'] { width: 180px; white-space: nowrap; vertical-align: top; padding-top: 8px; }
    #dlg table.edit.view input[type='text'] { width: 320px; max-width: 100%; }
    #dlg table.edit.view select[multiple] { min-width: 320px; min-height: 90px; }
<?php } ?>
</style>
<?php // Same containers as the legacy dashboard: the theme styles dashlets through them (pagination buttons...). ?>
<div class="dashboard">
    <div id="pageContainer" class="yui-skin-sam">
        <div id="pageNum_0_div">
            <table class="dashletTable">
                <tr>
                    <td class="dashletcontainer" valign="top">
                        <ul class="noBullet" id="col_0_0">
                            <li class="noBullet" id="dashlet_<?php echo $id; ?>">
                                <div id="dashlet_entire_<?php echo $id; ?>" class="dashletPanel">
                                    <?php echo $script, $dashlet->getHeader(), $display, $dashlet->getFooter(); ?>
                                </div>
                            </li>
                        </ul>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>
<script type="text/javascript">
    // The frame is narrower than the window: the theme's responsive rules would give the
    // dashlet its tablet or phone layout (no SugarFeed post form under 980px...). The
    // media queries are evaluated as in the window, as on the legacy dashboard.
    // The theme stylesheets are shared by the frames of the dashboard (the browser
    // reverts changes made to their rules), so they are replaced by copies of this page
    // (constructed stylesheets), in the same order, with the media queries resolved.
    (function () {
        var top;
        try {
            top = window.parent !== window ? window.parent : null;
        } catch (e) {
            top = null;
        }
        if (!top || !top.matchMedia || !document.adoptedStyleSheets || !CSSStyleSheet.prototype.replaceSync) {
            return;
        }

        var cssText = function (rules) {
            return Array.prototype.map.call(rules, function (rule) {
                if (rule.type === CSSRule.MEDIA_RULE) {
                    return top.matchMedia(rule.media.mediaText).matches ? cssText(rule.cssRules) : '';
                }
                if (rule.type === CSSRule.IMPORT_RULE) {
                    return rule.styleSheet ? cssText(rule.styleSheet.cssRules) : '';
                }
                return rule.type === CSSRule.CHARSET_RULE ? '' : rule.cssText;
            }).join('\n');
        };
        var rulesOf = function (sheet) {
            try {
                return sheet.cssRules;
            } catch (e) {
                return null; // Other origin: kept as is.
            }
        };
        var hasMedia = function (rules) {
            return Array.prototype.some.call(rules, function (rule) {
                return rule.type === CSSRule.MEDIA_RULE
                    || (rule.type === CSSRule.IMPORT_RULE && rule.styleSheet && hasMedia(rule.styleSheet.cssRules));
            });
        };

        // Stylesheets replaced by a copy, from the first one with media queries on; the
        // ones before it stay (seen). A later pass only takes the stylesheets added since.
        var copies = [];
        var seen = [];
        var replaceStyleSheets = function () {
            var started = copies.length > 0;
            Array.prototype.forEach.call(document.styleSheets, function (sheet) {
                if (seen.indexOf(sheet) !== -1) {
                    return;
                }
                seen.push(sheet);
                var rules = rulesOf(sheet);
                if (!rules || sheet.disabled || (!started && !hasMedia(rules))) {
                    return;
                }
                started = true;
                var copy = new CSSStyleSheet({baseURL: sheet.href || document.baseURI});
                copy.replaceSync(cssText(rules));
                copies.push({sheet: sheet, copy: copy});
                document.adoptedStyleSheets = document.adoptedStyleSheets.concat([copy]);
                sheet.disabled = true;
            });
        };
        // When the window is resized (debounced: the copies are rebuilt).
        var refreshTimer = null;
        var refresh = function () {
            clearTimeout(refreshTimer);
            refreshTimer = setTimeout(function () {
                copies.forEach(function (item) {
                    item.copy.replaceSync(cssText(item.sheet.cssRules));
                });
            }, 200);
        };

        replaceStyleSheets();
        window.addEventListener('load', replaceStyleSheets);
        top.addEventListener('resize', refresh);
        window.addEventListener('pagehide', function () {
            top.removeEventListener('resize', refresh);
        });
    })();

    var activePage = 0;
    var theme = '<?php echo $theme; ?>';
    current_user_id = '<?php echo $current_user->id; ?>';
    jsChartsArray = new Array();
    var moduleName = 'Home';
    document.body.setAttribute('class', 'yui-skin-sam');
    var mySugarLoader = new YAHOO.util.YUILoader({
        require: ['my_sugar', 'sugar_charts'],
        skin: {base: 'blank', defaultSkin: ''},
        onSuccess: function () {
            initMySugar();
            initmySugarCharts();
            SUGAR.mySugar.homepage_dd = [];
            SUGAR.mySugar.sugarCharts.loadSugarCharts(activePage);
            <?php if ($configure) { ?>
            openOptions();
            <?php } ?>
        }
    });
    <?php if ($configure) { ?>
    // The legacy options form, then a message to the options modal of the dashboard:
    // saved (the form posts, then uncoverPage() hides the dialog) or closed.
    var newTop = 0; // Read by configureDashlet() (set by the legacy dashboard).
    function openOptions() {
        var id = <?php echo json_encode($id); ?>;
        var saved = false;
        var notify = function () {
            if (window.parent !== window) {
                window.parent.postMessage({type: 'dashlet-options', id: id, saved: saved}, window.location.origin);
            }
        };
        var uncoverPage = SUGAR.mySugar.uncoverPage;
        SUGAR.mySugar.uncoverPage = function () {
            saved = true;
            return uncoverPage.apply(this, arguments);
        };
        SUGAR.mySugar.configureDashlet(id);
        if (typeof configureDlg !== 'undefined') {
            configureDlg.hideEvent.subscribe(notify);
        }
    }
    <?php } ?>
    mySugarLoader.addModule({
        name: 'my_sugar',
        type: 'js',
        fullpath: '<?php echo getJSPath('include/MySugar/javascript/MySugar.js'); ?>',
        varName: 'initMySugar',
        requires: []
    });
    mySugarLoader.addModule({
        name: 'sugar_charts',
        type: 'js',
        fullpath: '<?php echo getJSPath('include/SugarCharts/Jit/js/mySugarCharts.js'); ?>',
        varName: 'initmySugarCharts',
        requires: []
    });
    mySugarLoader.insert();
</script>
