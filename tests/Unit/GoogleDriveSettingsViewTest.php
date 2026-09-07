<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class GoogleDriveSettingsViewTest extends TestCase
{
    public function test_disconnect_form_posts_to_oauth_disconnect_and_is_not_nested(): void
    {
        $path = dirname(__DIR__, 2) . '/resources/views/admin/integrations/google_drive.blade.php';
        $this->assertFileExists($path);

        $blade = file_get_contents($path);
        $this->assertNotFalse($blade);

        $this->assertStringContainsString("route('integraciones.google-drive.oauth-disconnect')", $blade);
        $this->assertStringContainsString("route('integraciones.google-drive.oauth-redirect')", $blade);
        $this->assertStringContainsString('@csrf', $blade);

        preg_match_all('/<form\b[^>]*>|<\/form>/i', $blade, $matches);
        $this->assertNotEmpty($matches[0], 'Expected at least one form in the Google Drive settings view.');

        $depth = 0;
        $sawDisconnectForm = false;
        $disconnectInnerHtml = null;

        foreach ($matches[0] as $tag) {
            if (preg_match('/^<\/form>/i', $tag) === 1) {
                $this->assertGreaterThan(0, $depth, 'Found a closing </form> without a matching opening <form>.');
                $depth--;
                continue;
            }

            $this->assertSame(
                0,
                $depth,
                'Nested form found. Disconnect must not live inside the save form: ' . $tag
            );

            if (str_contains($tag, "integraciones.google-drive.oauth-disconnect")) {
                $sawDisconnectForm = true;
                $this->assertSame(0, $depth, 'Disconnect form must be a top-level form, not nested.');
                $disconnectInnerHtml = $this->formInnerHtml($blade, $tag);
            }

            $depth++;
        }

        $this->assertSame(0, $depth, 'Unbalanced <form> tags in google_drive.blade.php.');
        $this->assertTrue($sawDisconnectForm, 'Expected a POST form whose action is oauth-disconnect.');
        $this->assertNotNull($disconnectInnerHtml);
        $this->assertStringContainsString('@csrf', $disconnectInnerHtml);
        $this->assertStringContainsString('Desconectar', $disconnectInnerHtml);
    }

    public function test_connect_link_is_a_get_to_oauth_redirect_not_a_nested_form(): void
    {
        $path = dirname(__DIR__, 2) . '/resources/views/admin/integrations/google_drive.blade.php';
        $blade = file_get_contents($path);
        $this->assertNotFalse($blade);

        $this->assertMatchesRegularExpression(
            '/<a\b[^>]*href="\{\{\s*route\(\'integraciones\.google-drive\.oauth-redirect\'\)\s*\}\}"/',
            $blade
        );
    }

    private function formInnerHtml(string $blade, string $openingTag): string
    {
        $start = strpos($blade, $openingTag);
        $this->assertNotFalse($start);
        $afterOpen = $start + strlen($openingTag);
        $end = strpos($blade, '</form>', $afterOpen);
        $this->assertNotFalse($end);

        return substr($blade, $afterOpen, $end - $afterOpen);
    }
}
