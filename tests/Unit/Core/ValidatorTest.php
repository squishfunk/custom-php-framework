<?php

declare(strict_types=1);

namespace Tests\Core;

use App\Core\Validator;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    private Validator $validator;

    protected function setUp(): void
    {
        $this->validator = new Validator();
    }

    public function testValidateRequiredField(): void
    {
        $data = ['name' => 'John'];
        $rules = ['name' => 'required'];
        
        $result = $this->validator->validate($data, $rules);
        
        $this->assertTrue($result);
        $this->assertEmpty($this->validator->getErrors());
    }

    public function testValidateRequiredFieldFailsWhenEmpty(): void
    {
        $data = ['name' => ''];
        $rules = ['name' => 'required'];
        
        $result = $this->validator->validate($data, $rules);
        
        $this->assertFalse($result);
        $this->assertArrayHasKey('name', $this->validator->getErrors());
    }

    public function testValidateRequiredFieldFailsWhenNull(): void
    {
        $data = [];
        $rules = ['name' => 'required'];
        
        $result = $this->validator->validate($data, $rules);
        
        $this->assertFalse($result);
        $this->assertArrayHasKey('name', $this->validator->getErrors());
    }

    public function testValidateEmail(): void
    {
        $data = ['email' => 'test@example.com'];
        $rules = ['email' => 'email'];
        
        $result = $this->validator->validate($data, $rules);
        
        $this->assertTrue($result);
    }

    public function testValidateEmailFails(): void
    {
        $data = ['email' => 'invalid-email'];
        $rules = ['email' => 'email'];
        
        $result = $this->validator->validate($data, $rules);
        
        $this->assertFalse($result);
        $this->assertArrayHasKey('email', $this->validator->getErrors());
    }

    public function testValidateMinLength(): void
    {
        $data = ['password' => '123456'];
        $rules = ['password' => 'min:6'];
        
        $result = $this->validator->validate($data, $rules);
        
        $this->assertTrue($result);
    }

    public function testValidateMinLengthFails(): void
    {
        $data = ['password' => '123'];
        $rules = ['password' => 'min:6'];
        
        $result = $this->validator->validate($data, $rules);
        
        $this->assertFalse($result);
        $this->assertArrayHasKey('password', $this->validator->getErrors());
    }

    public function testValidateMaxLength(): void
    {
        $data = ['username' => 'john'];
        $rules = ['username' => 'max:10'];
        
        $result = $this->validator->validate($data, $rules);
        
        $this->assertTrue($result);
    }

    public function testValidateMaxLengthFails(): void
    {
        $data = ['username' => 'thisisaverylongusername'];
        $rules = ['username' => 'max:10'];
        
        $result = $this->validator->validate($data, $rules);
        
        $this->assertFalse($result);
        $this->assertArrayHasKey('username', $this->validator->getErrors());
    }

    public function testValidateNumeric(): void
    {
        $data = ['age' => '25'];
        $rules = ['age' => 'numeric'];
        
        $result = $this->validator->validate($data, $rules);
        
        $this->assertTrue($result);
    }

    public function testValidateNumericWithFloat(): void
    {
        $data = ['price' => '19.99'];
        $rules = ['price' => 'numeric'];
        
        $result = $this->validator->validate($data, $rules);
        
        $this->assertTrue($result);
    }

    public function testValidateNumericFails(): void
    {
        $data = ['age' => 'twenty-five'];
        $rules = ['age' => 'numeric'];
        
        $result = $this->validator->validate($data, $rules);
        
        $this->assertFalse($result);
        $this->assertArrayHasKey('age', $this->validator->getErrors());
    }

    public function testValidateDate(): void
    {
        $data = ['birthdate' => '1990-05-15'];
        $rules = ['birthdate' => 'date'];
        
        $result = $this->validator->validate($data, $rules);
        
        $this->assertTrue($result);
    }

    public function testValidateDateFails(): void
    {
        $data = ['birthdate' => 'not-a-date'];
        $rules = ['birthdate' => 'date'];
        
        $result = $this->validator->validate($data, $rules);
        
        $this->assertFalse($result);
        $this->assertArrayHasKey('birthdate', $this->validator->getErrors());
    }

    public function testValidateMultipleRules(): void
    {
        $data = [
            'email' => 'test@example.com',
            'password' => 'secure123'
        ];
        $rules = [
            'email' => 'required|email',
            'password' => 'required|min:6'
        ];
        
        $result = $this->validator->validate($data, $rules);
        
        $this->assertTrue($result);
        $this->assertEmpty($this->validator->getErrors());
    }

    public function testValidateMultipleRulesWithErrors(): void
    {
        $data = [
            'email' => 'invalid-email',
            'password' => '123'
        ];
        $rules = [
            'email' => 'required|email',
            'password' => 'required|min:6'
        ];
        
        $result = $this->validator->validate($data, $rules);
        
        $this->assertFalse($result);
        $errors = $this->validator->getErrors();
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('password', $errors);
    }

    public function testGetErrorsReturnsEmptyArrayInitially(): void
    {
        $this->assertEquals([], $this->validator->getErrors());
    }

    public function testValidateClearsPreviousErrors(): void
    {
        // First validation with errors
        $this->validator->validate(['name' => ''], ['name' => 'required']);
        $this->assertNotEmpty($this->validator->getErrors());
        
        // Second validation without errors
        $this->validator->validate(['name' => 'John'], ['name' => 'required']);
        $this->assertEmpty($this->validator->getErrors());
    }

    public function testValidateAllFields(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secret123',
            'age' => '30',
            'birthdate' => '1993-01-15'
        ];
        $rules = [
            'name' => 'required|min:3|max:50',
            'email' => 'required|email',
            'password' => 'required|min:8',
            'age' => 'required|numeric',
            'birthdate' => 'required|date'
        ];
        
        $result = $this->validator->validate($data, $rules);
        
        $this->assertTrue($result);
    }
}
