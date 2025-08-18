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

      // Build replacement markup
      $replacement = <<<HTML
                        <tr>
                          <td class="fieldlabel">
                            Consent to Publish Domain Information
                          </td>
                          <td class="fieldarea" colspan="3">
                            <label style="display:flex;align-items:flex-start;gap:.5rem;cursor:pointer;flex-direction: column;">
                              <input type="checkbox" name="consentForPublishing" value="1" {$checked} style="margin-top:2px;" />
                              <div>
                                <div style="font-weight:500;"></div>
                                <div role="note" aria-label="Privacy notice"
                                    style="margin-top:.5rem;padding:.5rem .75rem;border-left:4px solid #d9534f;background:#f9f2f4;">
                                  <div style="font-size:12px;line-height:1.45;">
                                    Your data is <strong>redacted by default</strong> to protect your privacy.<br>
                                    If you manually change this setting to allow publication, you understand and agree
                                    that the contact information will be treated as <strong>public and non-personal data</strong>.
                                  </div>
                                </div>
                              </div>
                            </label>
                          </td>
                        </tr>
                      HTML;

        // Safe-encode for JS template literal
        $replacementJson = json_encode($replacement, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT);

        return <<<HTML
                  <script>
                    (function() {
                      function inject() {
                        // Find original row by label text OR by input name
                        var \$row = jQuery("td.fieldlabel:contains('Consent to Publish Domain Information')").closest('tr');
                        if (!\$row.length) {
                          // Fallback by input name if needed
                          \$row = jQuery("input[name='domainfield\\\\[1\\\\]']").closest('tr');
                        }
                        
                        if (!\$row.length) {
                          return false;
                        }

                        var \$area = \$row.find('td.fieldarea');
                        if (!\$area.length) return false;

                        // Prevent duplicate injection
                        if (\$area.data('op-consent-injected') === true) {
                          return true;
                        }

                        // Replace the entire <tr> with your custom one
                        \$row.replaceWith({$replacementJson});
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
