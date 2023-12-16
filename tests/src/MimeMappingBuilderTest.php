<?php

namespace Elephox\Mimey\Tests;

use Elephox\Mimey\MimeTypes;
use Elephox\Mimey\MimeMappingBuilder;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class MimeMappingBuilderTest extends TestCase
{
	public function testFromEmpty(): void
	{
		$builder = MimeMappingBuilder::blank();
		$builder->add('foo/bar', 'foobar');
		$builder->add('foo/bar', 'bar');
		$builder->add('foo/baz', 'foobaz');
		$mime = new MimeTypes($builder->getMapping());
		$this->assertEquals('bar', $mime->getExtension('foo/bar'));
		$this->assertEquals(['bar', 'foobar'], $mime->getAllExtensions('foo/bar'));
		$this->assertEquals('foobaz', $mime->getExtension('foo/baz'));
		$this->assertEquals(['foobaz'], $mime->getAllExtensions('foo/baz'));
		$this->assertEquals('foo/bar', $mime->getMimeType('foobar'));
		$this->assertEquals(['foo/bar'], $mime->getAllMimeTypes('foobar'));
		$this->assertEquals('foo/bar', $mime->getMimeType('bar'));
		$this->assertEquals(['foo/bar'], $mime->getAllMimeTypes('bar'));
		$this->assertEquals('foo/baz', $mime->getMimeType('foobaz'));
		$this->assertEquals(['foo/baz'], $mime->getAllMimeTypes('foobaz'));
	}

	public function testFromBuiltIn(): void
	{
		$builder = MimeMappingBuilder::create();
		$mime1 = new MimeTypes($builder->getMapping());
		$this->assertEquals('json', $mime1->getExtension('application/json'));
		$this->assertEquals('application/json', $mime1->getMimeType('json'));

		$builder->add('application/json', 'mycustomjson');
		$mime2 = new MimeTypes($builder->getMapping());
		$this->assertEquals('mycustomjson', $mime2->getExtension('application/json'));
		$this->assertEquals('application/json', $mime2->getMimeType('json'));

		$builder->add('application/mycustomjson', 'json');
		$mime3 = new MimeTypes($builder->getMapping());
		$this->assertEquals('mycustomjson', $mime3->getExtension('application/json'));
		$this->assertEquals('application/mycustomjson', $mime3->getMimeType('json'));
	}

	public function testAppendExtension(): void
	{
		$builder = MimeMappingBuilder::blank();
		$builder->add('foo/bar', 'foobar');
		$builder->add('foo/bar', 'bar', false);
		$mime = new MimeTypes($builder->getMapping());
		$this->assertEquals('foobar', $mime->getExtension('foo/bar'));
	}

	public function testAppendMime(): void
	{
		$builder = MimeMappingBuilder::blank();
		$builder->add('foo/bar', 'foobar');
		$builder->add('foo/bar2', 'foobar', true, false);
		$mime = new MimeTypes($builder->getMapping());
		$this->assertEquals('foo/bar', $mime->getMimeType('foobar'));
	}

	/**
	 * @throws \JsonException
	 */
	public function testSave(): void
	{
		$builder = MimeMappingBuilder::blank();
		$builder->add('foo/one', 'one');
		$builder->add('foo/one', 'one1');
		$builder->add('foo/two', 'two');
		$builder->add('foo/two2', 'two');
		$file = tempnam(sys_get_temp_dir(), 'mapping_test');
		$builder->save($file);
		$mapping_included = json_decode(file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
		$this->assertEquals($builder->getMapping(), $mapping_included);
		$builder2 = MimeMappingBuilder::load($file);
		unlink($file);
		$this->assertEquals($builder->getMapping(), $builder2->getMapping());
	}

	public function testLoadInvalid(): void
	{
		$file = tempnam(sys_get_temp_dir(), 'mapping_test');
		file_put_contents($file, 'invalid json');

		$this->expectException(RuntimeException::class);

		MimeMappingBuilder::load($file);
	}

	public function testMultibyteExtension(): void
	{
		$builder = MimeMappingBuilder::blank();
		$builder->add('foo/bar', 'fôõbår');
		$mime = new MimeTypes($builder->getMapping());
		$this->assertEquals('fôõbår', $mime->getExtension('foo/bar'));
	}
}
