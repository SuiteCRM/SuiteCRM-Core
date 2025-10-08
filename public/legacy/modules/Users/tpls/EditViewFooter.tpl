{*
/**
 *
 * SugarCRM Community Edition is a customer relationship management program developed by
 * SugarCRM, Inc. Copyright (C) 2004-2013 SugarCRM Inc.
 *
 * SuiteCRM is an extension to SugarCRM Community Edition developed by SalesAgility Ltd.
 * Copyright (C) 2011 - 2018 SalesAgility Ltd.
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
*}
<!-- END METADATA GENERATED CONTENT -->

{{if $useTabs}}
<!-- include a closing div if the useTabs variable is set to true -->
</div>
{{/if}}
<div id="email_options" class="userprofile-email">
    <table width="100%" border="0" cellspacing="1" cellpadding="0" class="edit view">
        <tr>
            <th align="left" scope="row" colspan="4">
                <h4>{$MOD.LBL_MAIL_OPTIONS_TITLE}</h4>
            </th>
        </tr>
        <tr>
            <td scope="row" width="17%">
                {$MOD.LBL_EMAIL} {if $REQUIRED_EMAIL_ADDRESS}<span class="required"
                                                                   id="mandatory_email">{$APP.LBL_REQUIRED_SYMBOL}</span> {/if}
            </td>
            <td width="83%">
                {$NEW_EMAIL}
            </td>
        </tr>
        <tr id="email_options_link_type" style='display:{$HIDE_FOR_GROUP_AND_PORTAL}'>
            <td scope="row" width="17%">
                {$MOD.LBL_EMAIL_LINK_TYPE}:&nbsp;{sugar_help text=$MOD.LBL_EMAIL_LINK_TYPE_HELP WIDTH=450}
            </td>
            <td>
                <select id="email_link_type" name="email_link_type" tabindex='410'>
                    {$EMAIL_LINK_TYPE}
                </select>
            </td>
        </tr>

        <tr>
            <td scope="row" width="17%">{$MOD.LBL_EDITOR_TYPE}</td>
            <td width="83%">
                <select id="editor_type" name="editor_type" tabindex='410'>
                    {$EDITOR_TYPE}
                </select>
            </td>
        </tr>
    </table>
    {if $ID}
    <button class="button" id="settingsButton"
            onclick="SUGAR.email2.settings.showSettings(getUserEditViewUserId()); return false;"><img
            src="themes/default/images/icon_email_settings.gif" align="absmiddle"
            border="0"> {$APP.LBL_EMAIL_SETTINGS}</button>
    {/if}
</div>
</div>
<div class="user-tab-content">
    {if ($CHANGE_PWD) == '1'}
    <div id="generate_password">
        <table width="100%" border="0" cellspacing="0" cellpadding="0" class="edit view">
            <tr>
                <td width='100%'>
                    <table width='100%' cellspacing='0' cellpadding='0' border='0'>
                        <tr>
                            <th align="left" scope="row" colspan="4">
                                <h4>{$MOD.LBL_CHANGE_PASSWORD_TITLE}</h4><br>
                                {$ERROR_PASSWORD}
                            </th>
                        </tr>
                    </table>

                    <!-- hide field if user is admin that is not editing themselves -->
                    <div id='generate_password_old_password' {if ($IS_ADMIN && !$ADMIN_EDIT_SELF)}
                         style='display:none' {/if}>
                        <div class="old-password">
                            <div class="label-txt">
                                {$MOD.LBL_OLD_PASSWORD}
                            </div>
                            <div>
                                <input name='old_password' id='old_password' type='password' tabindex='2'
                                       onkeyup="password_confirmation();" autocomplete="new-password">
                            </div>
                            <div class="edit-dotted-border"></div>
                        </div>
                    </div>

                    <div class="password-row">
                        <!--left-col-->
                        <div class="left-col label-txt">
                            <div>
                                {$MOD.LBL_NEW_PASSWORD}
                                <span class="required"
                                      id="mandatory_pwd">{if ($REQUIRED_PASSWORD)}{$APP.LBL_REQUIRED_SYMBOL}{/if}</span>
                            </div>
                            <div class='dataField'>
                                <input name='new_password' id="new_password" type='password' tabindex='2'
                                       onkeyup="password_confirmation();newrules('{$PWDSETTINGS.minpwdlength}','{$PWDSETTINGS.maxpwdlength}','{$REGEX}');"/>
                            </div>
                            <div class="edit-dotted-border"></div>
                        </div>
                        <!-- right col -->
                        <div class="right-col label-txt">
                            <div>{$MOD.LBL_CONFIRM_PASSWORD}</div>
                            <div>
                                <input name='confirm_new_password' id='confirm_pwd' style='' type='password'
                                       tabindex='2' onkeyup="password_confirmation();">
                            </div>
                            <div>
                                <div id="comfirm_pwd_match" class="error"
                                     style="display: none;">{$MOD.ERR_PASSWORD_MISMATCH}</div>
                                {*<span id="ext-gen63" class="x-panel-header-text">
                                       Requirements
                                       <span id="Filter.1_help" onclick="return SUGAR.util.showHelpTips(this,help());">
                                           <img src="themes/default/images/help.gif"/>
                                       </span>
                                   </span>*}
                            </div>
                            <div class="edit-dotted-border"></div>
                        </div>
                        <!--extra div -->
                        <div>
                            <div class="dataLabel"></div>
                            <div class="dataLabel"></div>
                        </div>
                    </div>
                    <table width='17%' cellspacing='0' cellpadding='1' border='0'>
                        <tr>
                            <td width='50%'>
                               <input title="{$APP.LBL_SAVE_BUTTON_TITLE}" accessKey='{$APP.LBL_SAVE_BUTTON_KEY}'
                                class='button' id='save_new_pwd_button' LANGUAGE=javascript
                                onclick='if (set_password(this.form)) window.close(); else return false;'
                                type='submit' name='button' style='display:none;'
                                value='{$APP.LBL_SAVE_BUTTON_LABEL}'>
                            </td>
                            <td width='50%'>
                            </td>
                        </tr>
                    </table>
                </td>
                <td width='60%' style="vertical-align:middle;">
                </td>
            </tr>
        </table>
    </div>
    {else}
    <div id="generate_password">
        <input name='old_password' id='old_password' type='hidden'>
        <input name='new_password' id="new_password" type='hidden'>
        <input name='confirm_new_password' id='confirm_pwd' type='hidden'>
    </div>
    {/if}
</div>
{if $SHOW_THEMES}
<div class="user-tab-content">
    <div id="themepicker" style="display:{$HIDE_FOR_GROUP_AND_PORTAL}">
        <table class="edit view" border="0" cellpadding="0" cellspacing="0" width="100%">
            <tbody>
            <tr>
                <td scope="row" colspan="4"><h4>{$MOD.LBL_THEME}</h4></td>
            </tr>
            <tr>
                <td width="17%">
                    <select name="user_theme" tabindex='366' size="20" id="user_theme_picker" style="width: 100%">
                        {$THEMES}
                    </select>
                </td>
                <td width="33%">
                    <img id="themePreview" src="{sugar_getimagepath file='themePreview.png'}" border="1"/>
                </td>
                <td width="17%">&nbsp;</td>
                <td width="33%">&nbsp;</td>
            </tr>
            </tbody>
        </table>
    </div>
</div>
{/if}
<div class="user-tab-content">
    <!-- User Settings -->
    <div id="settings" style="display:{$HIDE_FOR_GROUP_AND_PORTAL}">
        <!--Heading-->
        <div>
            <h4>
                <slot>{$MOD.LBL_USER_SETTINGS}</slot>
            </h4>
        </div>
        <!--Main Content -->
        <div class="row-user">
            <!--First row-->
            <div class="row-container">
                <div class="left-col d-flex flex-column">
                    <div class="row-label">
                        <slot>{$MOD.LBL_EXPORT_DELIMITER}:</slot>&nbsp;{sugar_help text=$MOD.LBL_EXPORT_DELIMITER_DESC }
                    </div>
                    <div class="row-bottom flex-grow-1">
                        <slot><input style="line-height:21.5px;" type="text" tabindex='12' name="export_delimiter"
                                     value="{$EXPORT_DELIMITER}" size="5">
                        </slot>
                    </div>
                    <div class="bottom-dotted-border"></div>
                </div>
                <div class="right-col d-flex flex-column">
                    <div class="row-label">
                        <slot>{$MOD.LBL_RECEIVE_NOTIFICATIONS}:</slot>&nbsp;{sugar_help text=$MOD.LBL_RECEIVE_NOTIFICATIONS_TEXT}
                    </div>
                    <div class="row-bottom flex-grow-1">
                        <slot style="line-height: 2.3">
                            <input name='receive_notifications' class="checkbox" tabindex='12' type="checkbox"
                            value="12" {$RECEIVE_NOTIFICATIONS} style="margin-top: -1em;">
                        </slot>
                    </div>
                    <div class="bottom-dotted-border"></div>
                </div>
            </div>
            <div class="row-container">
                <div class="left-col d-flex flex-column">
                    <div class="row-label"><slot>{$MOD.LBL_EXPORT_CHARSET}:</slot>&nbsp;{sugar_help text=$MOD.LBL_EXPORT_CHARSET_DESC }</div>
                    <div class="row-bottom flex-grow-1"><slot><select tabindex='12' name="default_export_charset">{$EXPORT_CHARSET}</select></slot></div>
                    <div class="bottom-dotted-border"></div>
                </div>
                <div class="right-col d-flex flex-column">
                    <div class="row-label"><slot>{$MOD.LBL_REMINDER}:</slot>&nbsp;{sugar_help text=$MOD.LBL_REMINDER_TEXT }</div>
                    <div class="row-bottom"><slot>{include file="modules/Reminders/tpls/remindersDefaults.tpl"}</slot></div>
                    <div class="bottom-dotted-border"></div>
                </div>
            </div>
            <div class="row-container" >
                <div class="left-col d-flex flex-column">
                    <div class="row-label"><slot>{$MOD.LBL_DESKTOP_NOTIFICATIONS}:</slot></div>
                    <div class="row-bottom flex-grow-1">
                        <slot>
                            <button type="button" class="btn btn-primary btn-sm" onClick="Alerts.prototype.enable()">
                            {$MOD.LBL_ENABLE_NOTIFICATIONS}
                            </button>
                        </slot>
                    </div>
                    <div class="bottom-dotted-border"></div>
                </div>
                <div class="right-col d-flex flex-column" >
                    <div class="row-label"><slot>{$MOD.LBL_SNOOZE_TIMER}:</slot></div>
                    <div class="row-bottom flex-grow-1"><select tabindex='12' name="snooze_alert_timer">{$SNOOZE_ALERT_TIMER}</select></div>
                    <div class="bottom-dotted-border"></div>
                </div>
            </div>
            <div class="row-container">
                <div class="left-col d-flex flex-column">
                    <div class="row-label"><slot>{$MOD.LBL_USE_REAL_NAMES}:</slot>&nbsp;{sugar_help text=$MOD.LBL_USE_REAL_NAMES_DESC }</div>
                    <div class="row-bottom flex-grow-1"><slot style="line-height: 2.3"><input style="margin-top: -1em;" tabindex='12' type="checkbox" name="use_real_names" {$USE_REAL_NAMES}></slot></div>
                    <div class="bottom-dotted-border"></div>
                </div>
                <div class="right-col d-flex flex-column">
                    <div class="row-label"><slot>{$MOD.LBL_MAILMERGE}:</slot>&nbsp;{sugar_help text=$MOD.LBL_MAILMERGE_TEXT }</div>
                    <div class="row-bottom flex-grow-1">
                    <slot style="line-height: 1"><input style="margin-top: -1em;" tabindex='12' name='mailmerge_on' class="checkbox" type="checkbox" {$MAILMERGE_ON}>
                    </slot>
                    </div>
                    <div class="bottom-dotted-border"></div>
                </div>
            </div>
            <!--{if !empty($EXTERNAL_AUTH_CLASS) && !empty($IS_ADMIN)}-->
            <div class="row-container">
                <div>{capture name=SMARTY_LBL_EXTERNAL_AUTH_ONLY}&nbsp;{$MOD.LBL_EXTERNAL_AUTH_ONLY} {$EXTERNAL_AUTH_CLASS_1}{/capture}</div>
                <div class="left-col d-flex flex-column">
                    <div class="row-label"><slot>{$EXTERNAL_AUTH_CLASS} {$MOD.LBL_ONLY}:
                        </slot>&nbsp;{sugar_help text=$smarty.capture.SMARTY_LBL_EXTERNAL_AUTH_ONLY}</div>
                    <div class="row-bottom"> <input type='hidden' value='0' name='external_auth_only'><input type='checkbox' value='1'
                                                                                    name='external_auth_only' {$EXTERNAL_AUTH_ONLY_CHECKED}></div>
                    <div class="bottom-dotted-border"></div>
                </div>
            </div>
            <!--{/if}-->
        </div><!--row user-->
    </div>
    <!-- User Settings Ends here -->
    <div id="locale" style="display:{$HIDE_FOR_GROUP_AND_PORTAL}">
        <div>
            <h4>
                <slot>{$MOD.LBL_USER_LOCALE}</slot>
            </h4>
        </div>
        <div class="row-user">
            <div class="row-container">
                <div class="left-col">
                    <div class="row-label">
                        <slot>{$MOD.LBL_DATE_FORMAT}:</slot>&nbsp;{sugar_help text=$MOD.LBL_DATE_FORMAT_TEXT }</div>
                    <div class="row-bottom">
                        <slot><select tabindex='14' name='dateformat'>{$DATEOPTIONS}</select></slot>
                    </div>
                    <div class="bottom-dotted-border"></div>
                </div>
                <div class="right-col">
                    <div class="row-label">
                        <slot>{$MOD.LBL_CURRENCY}:</slot>&nbsp;{sugar_help text=$MOD.LBL_CURRENCY_TEXT }</div>
                    <div class="row-bottom">
                        <slot>
                            <select tabindex='14' id='currency_select' name='currency'
                                    onchange='setSymbolValue(this.options[this.selectedIndex].value);setSigDigits();'>{$CURRENCY}</select>
                            <input type="hidden" id="symbol" value="">
                        </slot>
                    </div>
                    <div class="bottom-dotted-border"></div>
                </div>
            </div>
            <div class="row-container">
                <div class="left-col">
                    <div class="row-label">
                        <slot>{$MOD.LBL_TIME_FORMAT}:</slot>&nbsp;{sugar_help text=$MOD.LBL_TIME_FORMAT_TEXT }</div>
                    <div class="row-bottom">
                        <slot><select tabindex='14' name='timeformat'>{$TIMEOPTIONS}</select></slot>
                    </div>
                    <div class="bottom-dotted-border"></div>
                </div>
                <div class="right-col">
                    <div class="row-label">
                        <slot>{$MOD.LBL_CURRENCY_SIG_DIGITS}:</slot>
                    </div>
                    <div class="row-bottom">
                        <slot>
                            <select id='sigDigits' onchange='setSigDigits(this.value);'
                                    name='default_currency_significant_digits'>{$sigDigits}</select>
                        </slot>
                    </div>
                    <div class="bottom-dotted-border"></div>
                </div>
            </div>
            <div class="row-container">
                <div class="left-col">
                    <div class="row-label">
                        <slot>{$MOD.LBL_TIMEZONE}:</slot>&nbsp;{sugar_help text=$MOD.LBL_TIMEZONE_TEXT }</div>
                    <div class="row-bottom">
                        <slot><select tabindex='14'
                                      name='timezone'>{html_options options=$TIMEZONEOPTIONS selected=$TIMEZONE_CURRENT}</select>
                        </slot>
                    </div>
                    <div class="bottom-dotted-border"></div>
                </div>
                <div class="right-col">
                    <div class="row-label">
                        <slot>
                            {$MOD.LBL_LOCALE_EXAMPLE_NAME_FORMAT}:
                        </slot>
                    </div>
                    <div class="row-bottom">
                        <slot>
                            <input type="text" disabled id="sigDigitsExample" name="sigDigitsExample">
                        </slot>
                    </div>
                    <div class="bottom-dotted-border"></div>
                </div>
            </div>
            <div class="row-container">
                <div class="left-col">
                    {if ($IS_ADMIN)}
                    <div class="row-label">
                        <slot>{$MOD.LBL_PROMPT_TIMEZONE}:</slot>&nbsp;{sugar_help text=$MOD.LBL_PROMPT_TIMEZONE_TEXT }
                    </div>
                    <div class="row-bottom">
                        <slot><input type="checkbox" tabindex='14' class="checkbox" name="ut" {$PROMPTTZ}>
                    </div>
                    {else}
                    <div>
                        <slot></slot>
                    </div>
                    {/if}
                    <div class="bottom-dotted-border"></div>
                </div>
                <div class="right-col">
                    <div class="row-label">
                        <slot>{$MOD.LBL_NUMBER_GROUPING_SEP}:
                        </slot>&nbsp;{sugar_help text=$MOD.LBL_NUMBER_GROUPING_SEP_TEXT }</div>
                        <div class="row-bottom">
                            <slot>
                                <input tabindex='14' name='num_grp_sep' id='default_number_grouping_seperator'
                                    type='text' maxlength='1' size='1' value='{$NUM_GRP_SEP}'
                                    onkeydown='setSigDigits();' onkeyup='setSigDigits();'>
                            </slot>
                        </div>
                        <div class="bottom-dotted-border"></div>
                    </div>
            </div>
            {capture name=SMARTY_LOCALE_NAME_FORMAT_DESC}&nbsp;{$MOD.LBL_LOCALE_NAME_FORMAT_DESC}{/capture}
            <div class="row-container">
                <div class="left-col">
                    <div class="row-label"> {$MOD.LBL_LOCALE_DEFAULT_NAME_FORMAT}
                        :&nbsp;{sugar_help text=$smarty.capture.SMARTY_LOCALE_NAME_FORMAT_DESC }</div>
                    <div class="row-bottom">
                        <slot><select tabindex='14' id="default_locale_name_format" name="default_locale_name_format"
                        selected="{$default_locale_name_format}">{$NAMEOPTIONS}</select></slot>
                    </div>
                    <div class="bottom-dotted-border"></div>
                </div>
                <div class="right-col">
                    <div class="row-label">
                        <slot>{$MOD.LBL_DECIMAL_SEP}:</slot>&nbsp;{sugar_help text=$MOD.LBL_DECIMAL_SEP_TEXT }
                    </div>
                    <div class="row-bottom">
                        <slot>
                            <input tabindex='14' name='dec_sep' id='default_decimal_seperator'
                            type='text' maxlength='1' size='1' value='{$DEC_SEP}'
                            onkeydown='setSigDigits();' onkeyup='setSigDigits();'>
                        </slot>
                    </div>
                    <div class="bottom-dotted-border"></div>
                </div>
            </div>
            <div class="row-container">
                <div class="right-col">
                    <div class="row-label">
                        <slot>{$MOD.LBL_USER_LANGUAGE}:</slot>
                    </div>

                    <div class="row-bottom">
                        <slot>
                        <select tabindex='15' id="language" name="language">{$LanguageOptions}</select>
                        </slot>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="calendar_options" style="display:{$HIDE_FOR_GROUP_AND_PORTAL}">
        <div>
            <h4>
                <slot>{$MOD.LBL_CALENDAR_OPTIONS}</slot>
            </h4>
        </div>
        <div class="row-user">
            <div class="row-container">
                <div style="width:100%;" class="row-left">
                    <div class="row-label">
                        <slot>{$MOD.LBL_PUBLISH_KEY}:</slot>&nbsp;{sugar_help text=$MOD.LBL_CHOOSE_A_KEY}</div>
                    <div class="row-bottom">
                        <slot><input id='calendar_publish_key' name='calendar_publish_key' tabindex='17' size='25'
                                 maxlength='36' type="text" value="{$CALENDAR_PUBLISH_KEY}"></slot>
                    </div>
                    <div class="bottom-dotted-border"></div>
                </div>
            </div>
            <div class="row-container">
                <div style="width:100%;" class="row-left">
                    <div class="row-label">
                        <slot>
                            <nobr>{$MOD.LBL_YOUR_PUBLISH_URL|strip_semicolon}:</nobr>
                        </slot>
                    </div>
                    <div class="row-bottom">
                        <span class="calendar_publish_ok">{$CALENDAR_PUBLISH_URL}</span>
                        <span class="calendar_publish_none" style="display: none">{$MOD.LBL_NO_KEY}</span>
                    </div>
                    <div class="bottom-dotted-border"></div>
                </div>
            </div>
            <div class="row-container">
                <div style="width:100%;" class="row-left">
                    <div class="row-label">
                        <slot>{$MOD.LBL_SEARCH_URL|strip_semicolon}:</slot>
                    </div>
                    <div class="row-bottom">
                        <span class="calendar_publish_ok">{$CALENDAR_SEARCH_URL}</span>
                        <span class="calendar_publish_none" style="display: none">{$MOD.LBL_NO_KEY}</span>
                    </div>
                    <div class="bottom-dotted-border"></div>
                </div>
            </div>
            <div class="row-container">
                <div style="width:100%;" class="row-left">
                    <div class="row-label">
                        <slot>{$MOD.LBL_ICAL_PUB_URL|strip_semicolon}: {sugar_help text=$MOD.LBL_ICAL_PUB_URL_HELP}</slot>
                    </div>
                    <div class="row-bottom">
                        <span class="calendar_publish_ok">{$CALENDAR_ICAL_URL}</span>
                        <span class="calendar_publish_none" style="display: none">{$MOD.LBL_NO_KEY}</span>
                    </div>
                    <div class="bottom-dotted-border"></div>
                </div>
            </div>
            <div class="row-container">
                <div style="width:100%;" class="row-left">
                    <div class="row-label">
                        <slot>{$MOD.LBL_FDOW}:</slot>&nbsp;{sugar_help text=$MOD.LBL_FDOW_TEXT}
                    </div>
                    <div class="row-bottom">
                        <slot>
                            <select tabindex='14'
                                name='fdow'>{html_options options=$FDOWOPTIONS selected=$FDOWCURRENT}</select>
                        </slot>
                    </div>
                    <div class="bottom-dotted-border" style="margin-bottom: 1em;"></div>
                </div>
            </div>
        </div>
    </div>
    <div id="google_options" style="display:{$HIDE_IF_GAUTH_UNCONFIGURED}">
        <table width="100%" border="0" cellspacing="1" cellpadding="0" class="edit view">
            <tr>
                <th align="left" scope="row" colspan="4"><h4>{$MOD.LBL_GOOGLE_API_SETTINGS}</h4></th>
            </tr>
            <tr>
                <td width="17%" scope="row">
                    <slot>{$MOD.LBL_GOOGLE_API_TOKEN}:</slot>&nbsp;{sugar_help text=$MOD.LBL_GOOGLE_API_TOKEN_HELP}
                </td>
                <td width="20%">
                    <slot>Current API Token is: <span style="color:{$GOOGLE_API_TOKEN_COLOR}">{$GOOGLE_API_TOKEN}</span>
                        &nbsp;&nbsp;<input style="display:{$GOOGLE_API_TOKEN_ENABLE_NEW}" class="btn btn-primary btn-sm"
                                       id="google_gettoken" type="button" value="{$GOOGLE_API_TOKEN_BTN}"
                                       onclick="window.open('{$GOOGLE_API_TOKEN_NEW_URL}', '_blank')"/></slot>
                </td>
                <td width="63%">
                    <slot>&nbsp;</slot>
                </td>
            </tr>
            <tr>
                <td width="17%" scope="row">
                    <slot>{$MOD.LBL_GSYNC_CAL}:</slot>
                </td>
                <td>
                    <slot><input tabindex='12' name='gsync_cal' class="checkbox" type="checkbox" {$GSYNC_CAL}></slot>
                </td>
            </tr>
        </table>
    </div>

</div>
{if $ID}
<div id="eapm_area" style='display:{$HIDE_FOR_GROUP_AND_PORTAL};' class="user-tab-content">
    <div style="text-align:center; width: 100%">{sugar_getimage name="loading"}</div>
</div>
{/if}

<div class="user-tab-content">
    <div id="subthemes" style="display:{$HIDE_FOR_GROUP_AND_PORTAL}">
        <div>
            <h4>
                <slot>{$MOD.LBL_LAYOUT_OPTIONS}</slot>
            </h4>
        </div>
        {if $SUBTHEMES}
        <div class="row-user layout">
            <div class="row-container">
                <div class="full-row">
                    <div class="row-label">{$MOD.LBL_SUBTHEME}:</div>
                    <div class="row-bottom"> {html_options name=subtheme options=$SUBTHEMES selected=$SUBTHEME}</div>
                    <div class="bottom-dotted-border"></div>
                </div>
            </div>
        </div>
        {/if}
        <div class="row-user layout">
            <div class="row-container">
                <div class="full-row" id="use_group_tabs_row" style="display: {$DISPLAY_GROUP_TAB};">
                    <div class="row-label">{$MOD.LBL_USE_GROUP_TABS}
                        {sugar_help text=$MOD.LBL_NAVIGATION_PARADIGM_DESCRIPTION }</div>
                    <div class="row-bottom"><input name="use_group_tabs" type="hidden" value="m"><input
                            id="use_group_tabs"
                            type="checkbox"
                            name="use_group_tabs" {$USE_GROUP_TABS}
                            tabindex='12' value="gm"></div>
                    <div class="bottom-dotted-border"></div>
                </div>
            </div>
        </div>
        <div class="row-user">
            <div class="row-container" style="display: block;">
                <div>{$TAB_CHOOSER}</div>
            </div>
            <div class="row-container">
                <div class="left-col" style="margin-right: 0;">
                    <div class="row-label">
                        {$MOD.LBL_SORT_MODULES}
                        {sugar_help text=$MOD.LBL_SORT_MODULES_DESCRIPTION }
                    </div>
                    <div class="row-bottom"><input class="subthemelayout_options_checkbox" type="checkbox"
                                                   name="sort_modules_by_name" {$SORT_MODULES_BY_NAME} tabindex='13'>
                    </div>
                    <div class="bottom-dotted-border"></div>
                </div>
                <div class="right-col" style="padding-left: 1em;">
                    <div class="row-label">
                        {$MOD.LBL_SUBPANEL_TABS}
                        {sugar_help text=$MOD.LBL_SUBPANEL_TABS_DESCRIPTION }
                    </div>
                    <div class="row-bottom"><input class="subthemelayout_options_checkbox" type="checkbox"
                                                   name="user_subpanel_tabs" {$SUBPANEL_TABS}
                                                   tabindex='13'></div>
                    <div class="bottom-dotted-border"></div>
                </div>
            </div>
            <div class="row-container">
                <div class="left-col">
                    <div class="row-label">{$MOD.LBL_COUNT_COLLAPSED_SUBPANELS}
                        {sugar_help text=$MOD.LBL_COUNT_COLLAPSED_SUBPANELS_DESCRIPTION }
                    </div>
                    <div class="row-bottom"><input class="subthemelayout_options_checkbox" type="checkbox"
                                                   name="user_count_collapsed_subpanels" {$COUNT_COLLAPSED_SUBPANELS}
                                                   tabindex='13'></div>
                    <div class="bottom-dotted-border"></div>
                </div>
            </div>
            <div class="row-container load-more">
                <div class="left-col">
                    <div class="row-label">{$MOD.LBL_SET_SUBPANEL_PAGINATION_TYPE}: </div>
                    <div>
                        <select name="subpanel_pagination_type">{$subpanel_pagination_type}</select>
                    </div>
                    <div class="row-label">{$MOD.LBL_SET_LISTVIEW_PAGINATION_TYPE}: </div>
                    <div>
                        <select name="listview_pagination_type">{$listview_pagination_type}</select>
                    </div>
                    <div class="row-label">{$MOD.LBL_SET_RECORD_MODAL_PAGINATION_TYPE}: </div>
                    <div>
                        <select name="record_modal_pagination_type">{$record_modal_pagination_type}</select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="user-tab-content">
    <div id="two-factor-auth">
        <div class="row-user layout">
            <div class="row-container">
                <div class="full-row">
                    <div class="row-label">{$MOD.LBL_STATUS}: <span id="auth_status">{$STATUS}</span></div>
                    <div class="bottom-dotted-border"></div>
                </div>
            </div>
        </div>
    {if $CURRENT_USER}
    <div class="row-user layout">
        <div class="row-container">
            <div class="full-row">
                <div class="row-label">{$MOD.LBL_FACTOR_AUTH}:</div>
                    <div class="row-bottom"> <button type="button" class="btn btn-primary btn-sm" onclick="window.location.href = './#/users/2fa-config'">{$MOD.LBL_2FA_CONFIG}</button></div>
                    <div class="bottom-dotted-border"></div>
                </div>
            </div>
        </div>
    {/if}
    </div>
</div>
</div>
</div>

<script type="text/javascript">

    var mail_smtpport = '{$MAIL_SMTPPORT}';
    var mail_smtpssl = '{$MAIL_SMTPSSL}';
    {literal}
    EmailMan = {};

    document.getElementById('auth_status').style.color = '{/literal}{$STATUS_COLOR}{literal}';

    function Admin_check() {
        if (('{/literal}{$IS_FOCUS_ADMIN}{literal}') && document.getElementById('is_admin').value == '0') {
            r = confirm('{/literal}{$MOD.LBL_CONFIRM_REGULAR_USER}{literal}');
            return r;
        } else {
            return true;
        }
    }


  $(document).ready(function () {
    var checkKey = function (key) {
      var validation = /^[A-Z0-9\-_.]*$/i;
      if (key != '' && validation.test(key)) {

        var encodedKey = key.replace(/[&<>'"]/g, function(tag) {
          return ({
              '&': '&amp;',
              '<': '&lt;',
              '>': '&gt;',
              "'": '&#39;',
              '"': '&quot;'
            }[tag]);
        })
        $(".calendar_publish_ok").css('display', 'inline');
        $(".calendar_publish_none").css('display', 'none');
        $('#cal_pub_key_span').html(encodedKey);
        $('#ical_pub_key_span').html(encodedKey);
        $('#search_pub_key_span').html(encodedKey);
      } else {
        $(".calendar_publish_ok").css('display', 'none');
        $(".calendar_publish_none").css('display', 'inline');
      }
    };
    $('#calendar_publish_key').keyup(function () {
      checkKey($(this).val());
    });
    $('#calendar_publish_key').change(function () {
      checkKey($(this).val());
    });
    checkKey($('#calendar_publish_key').val());
  });
  {/literal}
</script>
{$JAVASCRIPT}
{literal}
<script type="text/javascript" language="Javascript">
    {/literal}
    {$getNameJs}
    {$getNumberJs}
    currencies = {$currencySymbolJSON};
    themeGroupList = {$themeGroupListJSON};

    onUserEditView();


</script>

</form>

<div id="testOutboundDialog" class="yui-hidden">
    <div id="testOutbound">
        <form>
            <table width="100%" border="0" cellspacing="0" cellpadding="0" class="edit view">
                <tr>
                    <td scope="row">
                        {$APP.LBL_EMAIL_SETTINGS_FROM_TO_EMAIL_ADDR}
                        <span class="required">
						{$APP.LBL_REQUIRED_SYMBOL}
					</span>
                    </td>
                    <td>
                        <input type="text" id="outboundtest_from_address" name="outboundtest_from_address" size="35"
                               maxlength="64" value="{$TEST_EMAIL_ADDRESS}">
                    </td>
                </tr>
                <tr>
                    <td scope="row" colspan="2">
                        <input type="button" class="button" value="   {$APP.LBL_EMAIL_SEND}   "
                               onclick="sendTestEmail();">&nbsp;
                        <input type="button" class="button" value="   {$APP.LBL_CANCEL_BUTTON_LABEL}   "
                               onclick="EmailMan.testOutboundDialog.hide();">&nbsp;
                    </td>
                </tr>
            </table>
        </form>
    </div>
</div>
{literal}
<style>
    .actionsContainer.footer td {
        height: 120px;
        vertical-align: top;
    }
</style>
{/literal}
<table width="100%" cellpadding="0" cellspacing="0" border="0" class="actionsContainer footer">
    <tr>
        <td class="actionbutton-footer">
            {sugar_action_menu id="userEditActions" class="clickMenu fancymenu" buttons=$ACTION_BUTTON_FOOTER flat=true}
        </td>
        <td align="right" nowrap>
            <span class="required">{$APP.LBL_REQUIRED_SYMBOL}</span> {$APP.NTC_REQUIRED}
        </td>
    </tr>
</table>

{if $showEmailSettingsPopup}
<script>
    {literal}
    $(function() {
        SUGAR.email2.settings.showSettings();
    });
    {/literal}
</script>
{/if}
