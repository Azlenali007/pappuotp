<?php
/**
 * Transactional Email Templates Helper
 */

function generate_email_html(string $title, string $bodyContent, string $buttonText = '', string $buttonUrl = ''): string {
    $btn = '';
    if ($buttonText && $buttonUrl) {
        $btn = '<div style="text-align: center; margin: 30px 0;">
            <a href="' . htmlspecialchars($buttonUrl) . '" style="background: #7c3aed; color: #ffffff; padding: 12px 28px; text-decoration: none; border-radius: 8px; font-weight: 600; display: inline-block;">' . htmlspecialchars($buttonText) . '</a>
        </div>';
    }

    return '<!DOCTYPE html>
    <html>
    <head><meta charset="utf-8"></head>
    <body style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; background: #f8fafc; margin: 0; padding: 40px 20px;">
        <table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
                <td align="center">
                    <table width="600" border="0" cellspacing="0" cellpadding="0" style="background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
                        <tr>
                            <td style="background: #7c3aed; padding: 25px 30px; text-align: left;">
                                <h2 style="color: #ffffff; margin: 0; font-size: 20px; font-weight: 700;">OTP Services</h2>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 35px 30px; color: #334155; line-height: 1.6; font-size: 15px;">
                                <h1 style="color: #0f172a; font-size: 20px; margin-top: 0;">' . htmlspecialchars($title) . '</h1>
                                ' . $bodyContent . '
                                ' . $btn . '
                                <p style="font-size: 13px; color: #64748b; margin-top: 30px; border-top: 1px solid #f1f5f9; padding-top: 20px;">
                                    If you did not make this request, please contact our security team immediately.
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>';
}
