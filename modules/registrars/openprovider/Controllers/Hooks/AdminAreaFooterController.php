<?php

namespace OpenProvider\WhmcsRegistrar\Controllers\Hooks;

/**
 * Class AdminAreaFooterController
 * OpenProvider Registrar module
 *
 * @copyright Copyright (c) Openprovider 2025
 */


class AdminAreaFooterController
{

  /**
   * AdminClientProfileTabController constructor.
   */
  public function __construct()
  {
  }

  public function output($vars)
    {
      // Only on the Admin -> Domain Information page
      if (($vars['filename'] ?? '') !== 'clientsdomains') {
          return '';
      }

      // Domain id from query string (?id=...)
      $domainId = (int)($_GET['id'] ?? 0);
      if (!$domainId) {
          return '';
      }

    $cached = $_SESSION['admin_area_op_domain_info'][$domainId] ?? null;
      if (!$cached) {
          return '';
      }

    $checked = $cached['consentForPublishing'] ? 'checked' : '';
    $opDomainId  = (string)($cached['opDomainId'] ?? '');

    $checkedJson = json_encode($checked, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    $opDomainIdJson = json_encode($opDomainId, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

        return <<<HTML
                  <script>
                    (function() {
                      function inject() {
                        // Find original row by label text OR by input name
                        var \$row = jQuery("td.fieldlabel:contains('Consent to Publish Domain Information')").closest('tr');
                        
                        if (!\$row.length) return false;

                        // Read original input name and checked state
                        var \$orig = \$row.find("input[type='checkbox']");
                        var inputName = \$orig.attr('name');
                        // PHP-provided checked state
                        var isChecked = {$checkedJson} === 'checked';
                        var opDomainId = {$opDomainIdJson};

                        var \$area = \$row.find('td.fieldarea');
                        if (!\$area.length) return false;

                        // Prevent duplicate injection
                        if (\$area.data('op-consent-injected') === true) {
                          return true;
                        }

                        var idSuffix = '{$domainId}'; // make IDs unique per domain
                        var checkboxId = 'op_consent_checkbox_' + idSuffix;
                        var hiddenMirrorId = 'op_consent_value_' + idSuffix;

                        // Build the replacement <tr> using the discovered input name
                        var newRow = `
                          <tr>
                            <td class="fieldlabel">Consent to Publish Domain Information</td>
                            <td class="fieldarea" colspan="3">
                              <label style="display:flex;flex-direction:column;align-items:flex-start;gap:.5rem;cursor:pointer;">
                                <!-- Always submit a value: hidden=0, checkbox=1 -->
                                <input type="hidden" name="\${inputName}" value="0">
                                <div style="display:flex;align-items:center;gap:.5rem;">
                                  <input type="checkbox" name="\${inputName}" value="1" \${isChecked ? 'checked' : ''} id="\${checkboxId}"  style="margin-top:0;">
                                </div>
                                <!-- Mirror field that always posts 0/1 for server-side convenience -->
                                <input type="hidden" name="op_consent_value" id="\${hiddenMirrorId}" value="\${isChecked ? '1' : '0'}">
                                <!-- Openprovider domain id for save() -->
                                <input type="hidden" name="op_domain_id" value="\${opDomainId}">
                                <div role="note" aria-label="Privacy notice"
                                    style="margin-top:.25rem;padding:.5rem .75rem;border-left:4px solid #d9534f;background:#f9f2f4;">
                                  <div style="font-size:12px;line-height:1.45;">
                                    Your data is <strong>redacted by default</strong> to protect your privacy.<br>
                                    If you manually change this setting to allow publication, you understand and agree
                                    that the contact information will be treated as <strong>public and non-personal data</strong>.
                                  </div>
                                </div>
                              </label>
                            </td>
                          </tr>`.trim();

                        // Replace the entire <tr> with your custom one
                        \$row.replaceWith(newRow);

                        // Wire mirror: keep op_consent_value in sync with checkbox
                        var \$box    = jQuery('#' + checkboxId);
                        var \$hidden = jQuery('#' + hiddenMirrorId);
                        function syncMirror(){ \$hidden.val(\$box.is(':checked') ? '1' : '0'); }
                        syncMirror();
                        \$box.on('change', syncMirror);

                        // Mark as injected
                        \$row.data('op-consent-injected', true);

                        return true;
                      }
                      
                      // Run when DOM and jQuery are ready
                      jQuery(function($) {
                        // Try immediately
                        if (inject()) return;

                        // Retry a few times (for late-rendered content)
                        var attempts = 0;
                        var timer = setInterval(function() {
                          attempts++;
                          if (inject() || attempts > 10) {
                            clearInterval(timer);
                          }
                        }, 200);

                        // Observe dynamic changes (some themes update the form via JS)
                        var target = document.querySelector('table.form') || document.body;
                        if (target && window.MutationObserver) {
                          var mo = new MutationObserver(function() { inject(); });
                          mo.observe(target, { childList: true, subtree: true });
                        }
                      });
                    })();
                  </script>
                HTML;
    }
}
