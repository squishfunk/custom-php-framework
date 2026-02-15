<?php

declare(strict_types=1);

namespace Tests\Core;

use App\Core\Config;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    public function testLoadConfig(): void
    {
        $tempFile = sys_get_temp_dir() . '/config_test_' . uniqid() . '.php';
        file_put_contents($tempFile, '<?php return ["app" => ["name" => "TestApp"]];');
        
        Config::load($tempFile);
        
        $this->assertEquals('TestApp', Config::get('app.name'));
        
        unlink($tempFile);
    }

    public function testGetNestedValue(): void
    {
        $tempFile = sys_get_temp_dir() . '/config_test_' . uniqid() . '.php';
        file_put_contents($tempFile, '<?php return ["database" => ["host" => "localhost", "port" => 3306]];');
        
        Config::load($tempFile);
        
        $this->assertEquals('localhost', Config::get('database.host'));
        $this->assertEquals(3306, Config::get('database.port'));
        
        unlink($tempFile);
    }

    public function testGetNonExistentKeyReturnsNull(): void
    {
        $tempFile = sys_get_temp_dir() . '/config_test_' . uniqid() . '.php';
        file_put_contents($tempFile, '<?php return ["app" => ["name" => "TestApp"]];');
        
        Config::load($tempFile);
        
        $this->assertNull(Config::get('nonexistent'));
        $this->assertNull(Config::get('app.nonexistent'));
        $this->assertNull(Config::get('database.host'));
        
        unlink($tempFile);
    }

    public function testGetArrayValue(): void
    {
        $tempFile = sys_get_temp_dir() . '/config_test_' . uniqid() . '.php';
        file_put_contents($tempFile, '<?php return ["database" => ["host" => "localhost", "port" => 3306]];');
        
        Config::load($tempFile);
        
        $database = Config::get('database');
        $this->assertIsArray($database);
        $this->assertEquals(['host' => 'localhost', 'port' => 3306], $database);
        
        unlink($tempFile);
    }

    public function testGetSingleLevelKey(): void
    {
        $tempFile = sys_get_temp_dir() . '/config_test_' . uniqid() . '.php';
        file_put_contents($tempFile, '<?php return ["debug" => true, "version" => "1.0.0"];');
        
        Config::load($tempFile);
        
        $this->assertTrue(Config::get('debug'));
        $this->assertEquals('1.0.0', Config::get('version'));
        
        unlink($tempFile);
    }
}
