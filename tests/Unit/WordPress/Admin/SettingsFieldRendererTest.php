<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\WordPress\Admin;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;
use WpCaptchaShield\WordPress\Admin\SettingsFieldRenderer;

final class SettingsFieldRendererTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Monkey\setUp();

        Functions\when('__')->returnArg();
        Functions\when('esc_html')->returnArg();
        Functions\when('esc_html__')->returnArg();
        Functions\when('esc_attr')->returnArg();
        Functions\when('selected')->justReturn('');
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        Mockery::close();

        parent::tearDown();
    }

    public function testItRendersATextFieldWithItsValueAndHelp(): void
    {
        $renderer = new SettingsFieldRenderer();

        ob_start();
        $renderer->renderTextField(
            'field-id',
            'Field label',
            'field[name]',
            'field-value',
            help: 'Field help text',
        );
        $output = ob_get_clean();

        self::assertIsString($output);
        self::assertStringContainsString('field-value', $output);
        self::assertStringContainsString('Field label', $output);
        self::assertStringContainsString('role="tooltip"', $output);
        self::assertStringContainsString('Field help text', $output);
    }

    public function testItRendersASecretFieldWithoutTheStoredValue(): void
    {
        $renderer = new SettingsFieldRenderer();

        ob_start();
        $renderer->renderSecretField(
            'secret-id',
            'Secret label',
            'secret[name]',
            true,
        );
        $output = ob_get_clean();

        self::assertIsString($output);
        self::assertStringContainsString('type="password"', $output);
        self::assertStringContainsString(
            'A value is stored. Leave blank to keep it unchanged.',
            $output,
        );
    }

    public function testItOmitsTheStoredValueNoticeWhenNothingIsStored(): void
    {
        $renderer = new SettingsFieldRenderer();

        ob_start();
        $renderer->renderSecretField(
            'secret-id',
            'Secret label',
            'secret[name]',
            false,
        );
        $output = ob_get_clean();

        self::assertIsString($output);
        self::assertStringNotContainsString(
            'A value is stored. Leave blank to keep it unchanged.',
            $output,
        );
    }

    public function testItRendersASelectFieldWithItsOptions(): void
    {
        $renderer = new SettingsFieldRenderer();

        ob_start();
        $renderer->renderSelectField(
            'select-id',
            'Select label',
            'select[name]',
            'b',
            ['a' => 'Option A', 'b' => 'Option B'],
        );
        $output = ob_get_clean();

        self::assertIsString($output);
        self::assertStringContainsString('Option A', $output);
        self::assertStringContainsString('Option B', $output);
    }

    public function testItOmitsTheHelpTooltipWhenNoHelpIsGiven(): void
    {
        $renderer = new SettingsFieldRenderer();

        ob_start();
        $renderer->renderFieldHelp('field-id', 'Field label', null);
        $output = ob_get_clean();

        self::assertSame('', $output);
    }
}
