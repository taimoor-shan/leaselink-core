<?php
/**
 * Tests for SRP_Template_Loader — Template Location and Override Logic.
 *
 * @package StudentRentalPlatform\Tests\Unit
 */

namespace StudentRentalPlatform\Tests\Unit;

use WP_UnitTestCase;
use StudentRentalPlatform\SRP_Template_Loader;

/**
 * Class TemplateLoaderTest
 *
 * Tests the WooCommerce-style template override system.
 */
class TemplateLoaderTest extends WP_UnitTestCase
{
    /**
     * @test
     */
    public function plugin_templates_path_returns_correct_directory(): void
    {
        $path = SRP_Template_Loader::plugin_templates_path();

        $this->assertStringEndsWith('/templates/', $path);
        $this->assertDirectoryExists($path, 'Plugin templates directory should exist.');
    }

    /**
     * @test
     */
    public function theme_path_returns_leaselink_subdirectory(): void
    {
        $path = SRP_Template_Loader::theme_path();

        $this->assertEquals('leaselink/', $path);
    }

    /**
     * @test
     */
    public function locate_finds_plugin_default_template(): void
    {
        $located = SRP_Template_Loader::locate('components/badge.php');

        $this->assertNotFalse($located, 'locate() should find the plugin default badge template.');
        $this->assertFileExists($located);
        $this->assertStringContainsString('templates/components/badge.php', $located);
    }

    /**
     * @test
     */
    public function locate_returns_false_for_nonexistent_template(): void
    {
        $located = SRP_Template_Loader::locate('this-template-does-not-exist.php');

        $this->assertFalse($located, 'locate() should return false for a nonexistent template.');
    }

    /**
     * @test
     */
    public function locate_finds_dashboard_templates(): void
    {
        // Verify key dashboard templates are locatable.
        $templates = [
            'dashboard/landlord-dashboard.php',
            'dashboard/add-property.php',
            'components/stat-card.php',
            'components/empty-state.php',
            'components/dashboard-nav.php',
        ];

        foreach ($templates as $template) {
            $located = SRP_Template_Loader::locate($template);
            $this->assertNotFalse($located, "Should find template: {$template}");
            $this->assertFileExists($located);
        }
    }

    /**
     * @test
     */
    public function locate_finds_single_listing_template(): void
    {
        $located = SRP_Template_Loader::locate('single-cpt_listing.php');

        $this->assertNotFalse($located, 'locate() should find the listing single template.');
        $this->assertFileExists($located);
    }

    /**
     * @test
     */
    public function get_template_outputs_template_content(): void
    {
        // Capture output from get_template.
        ob_start();
        SRP_Template_Loader::get_template('components/badge.php', [
            'text' => 'Test Badge',
            'color' => 'primary',
        ]);
        $output = ob_get_clean();

        // Badge template should produce some HTML output.
        $this->assertNotEmpty($output, 'get_template() should produce output.');
        $this->assertStringContainsString('Test Badge', $output, 'Badge should render the text argument.');
    }

    /**
     * @test
     */
    public function get_template_with_nonexistent_file_produces_no_output(): void
    {
        ob_start();
        SRP_Template_Loader::get_template('nonexistent-template.php');
        $output = ob_get_clean();

        $this->assertEmpty($output, 'get_template() with nonexistent template should produce no output.');
    }

    /**
     * @test
     */
    public function locate_template_filter_is_applied(): void
    {
        $filter_called = false;

        add_filter('leaselink_locate_template', function ($template, $template_name) use (&$filter_called) {
            $filter_called = true;
            return $template;
        }, 10, 2);

        SRP_Template_Loader::locate('components/badge.php');

        $this->assertTrue($filter_called, 'The leaselink_locate_template filter should be called.');
    }

    /**
     * @test
     */
    public function page_map_contains_expected_slugs(): void
    {
        $expected_slugs = [
            'landlord-dashboard',
            'student-dashboard',
            'my-properties',
            'add-property',
            'my-applications',
            'landlord-applications',
            'login',
            'signup',
        ];

        foreach ($expected_slugs as $slug) {
            $this->assertArrayHasKey(
                $slug,
                SRP_Template_Loader::PAGE_MAP,
                "PAGE_MAP should contain slug: {$slug}"
            );
        }
    }
}
