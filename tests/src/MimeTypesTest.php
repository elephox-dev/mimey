<?php

namespace Elephox\Mimey\Tests;

use Elephox\Mimey\MimeTypes;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;

#[CoversClass(MimeTypes::class)]
class MimeTypesTest extends TestCase
{
	protected ?MimeTypes $mime = null;

	protected function setUp(): void
	{
		$this->mime = new MimeTypes([
			'mimes' => [
				'json' => ['application/json'],
				'jpeg' => ['image/jpeg'],
				'jpg' => ['image/jpeg'],
				'bar' => ['foo', 'qux'],
				'baz' => ['foo'],
			],
			'extensions' => [
				'application/json' => ['json'],
				'image/jpeg' => ['jpeg', 'jpg'],
				'foo' => ['bar', 'baz'],
				'qux' => ['bar'],
			],
		]);
	}

	public static function getMimeTypeProvider(): array
	{
		return [
			['application/json', 'json'],
			['image/jpeg', 'jpeg'],
			['image/jpeg', 'jpg'],
			['foo', 'bar'],
			['foo', 'baz'],
		];
	}

	#[DataProvider('getMimeTypeProvider')]
	public function testGetMimeType($expectedMimeType, $extension): void
	{
		$this->assertEquals($expectedMimeType, $this->mime->getMimeType($extension));
	}

	public static function getExtensionProvider(): array
	{
		return [
			['json', 'application/json'],
			['jpeg', 'image/jpeg'],
			['bar', 'foo'],
			['bar', 'qux'],
		];
	}

	#[DataProvider('getExtensionProvider')]
	public function testGetExtension($expectedExtension, $mimeType): void
	{
		$this->assertEquals($expectedExtension, $this->mime->getExtension($mimeType));
	}

	public static function getAllMimeTypesProvider(): array
	{
		return [
			[
				['application/json'], 'json',
			],
			[
				['image/jpeg'], 'jpeg',
			],
			[
				['image/jpeg'], 'jpg',
			],
			[
				['foo', 'qux'], 'bar',
			],
			[
				['foo'], 'baz',
			],
		];
	}

	#[DataProvider('getAllMimeTypesProvider')]
	public function testGetAllMimeTypes($expectedMimeTypes, $extension): void
	{
		$this->assertEquals($expectedMimeTypes, $this->mime->getAllMimeTypes($extension));
	}

	public static function getAllExtensionsProvider(): array
	{
		return [
			[
				['json'], 'application/json',
			],
			[
				['jpeg', 'jpg'], 'image/jpeg',
			],
			[
				['bar', 'baz'], 'foo',
			],
			[
				['bar'], 'qux',
			],
		];
	}

	#[DataProvider('getAllExtensionsProvider')]
	public function testGetAllExtensions($expectedExtensions, $mimeType): void
	{
		$this->assertEquals($expectedExtensions, $this->mime->getAllExtensions($mimeType));
	}

	public function testGetMimeTypeUndefined(): void
	{
		$this->assertNull($this->mime->getMimeType('undefined'));
	}

	public function testGetExtensionUndefined(): void
	{
		$this->assertNull($this->mime->getExtension('undefined'));
	}

	public function testGetAllMimeTypesUndefined(): void
	{
		$this->assertEquals([], $this->mime->getAllMimeTypes('undefined'));
	}

	public function testGetAllExtensionsUndefined(): void
	{
		$this->assertEquals([], $this->mime->getAllExtensions('undefined'));
	}

	public function testBuiltInMapping(): void
	{
		$mime = new MimeTypes();
		$this->assertEquals('json', $mime->getExtension('application/json'));
		$this->assertEquals('application/json', $mime->getMimeType('json'));
	}

	public function testInvalidBuiltInMapping(): void
	{
		$original = dirname(__DIR__, 2) . '/dist/mime.types.min.json';
		$backup = dirname(__DIR__, 2) . '/dist/mime.types.min.json.backup';
		rename($original, $backup);
		file_put_contents($original, 'invalid json');

		$class = new ReflectionClass(MimeTypes::class);
		$class->setStaticPropertyValue('built_in', null);

		try {
			$this->expectException(RuntimeException::class);
			new MimeTypes();
		} finally {
			unlink($original);
			rename($backup, $original);
		}
	}
}
