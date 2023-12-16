<?php

namespace Elephox\Mimey\Tests;

use Elephox\Mimey\MimeMappingGenerator;
use PHPUnit\Framework\TestCase;

class MimeMappingGeneratorTest extends TestCase
{
	public function testGenerateMapping(): void
	{
		$generator = new MimeMappingGenerator(
			"#ignore\tme\n" .
			"application/json\t\t\tjson\n" .
			"image/jpeg\t\t\tjpeg jpg #ignore this too\n\n" .
			"foo\tbar baz\n" .
			"qux\tbar\n"
		);
		$mapping = $generator->generateMapping();
		$expected = [
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
		];
		$this->assertEquals($expected, $mapping);
	}

	public function testGenerateJson(): void
	{
		$generator = new MimeMappingGenerator(<<<EOF
#ignore
application/json\tjson
image/jpeg\tjpeg jpg
EOF
		);

		$json = $generator->generateJson(false);
		$minJson = $generator->generateJson();

		self::assertEquals(<<<EOF
{
    "mimes": {
        "json": [
            "application\/json"
        ],
        "jpeg": [
            "image\/jpeg"
        ],
        "jpg": [
            "image\/jpeg"
        ]
    },
    "extensions": {
        "application\/json": [
            "json"
        ],
        "image\/jpeg": [
            "jpeg",
            "jpg"
        ]
    }
}
EOF, $json);

		self::assertEquals('{"mimes":{"json":["application\/json"],"jpeg":["image\/jpeg"],"jpg":["image\/jpeg"]},"extensions":{"application\/json":["json"],"image\/jpeg":["jpeg","jpg"]}}', $minJson);
	}

	public function testGeneratePhpEnum(): void
	{
		$generator = new MimeMappingGenerator(<<<EOF
#ignore
application/json\tjson
image/jpeg\tjpeg jpg
EOF
		);

		$phpEnum = $generator->generatePhpEnum("TestMimeClass", "TestMimeNamespace", withOverrideAttribute: false);

		self::assertEquals(<<<EOF
<?php
/**
 * @generated This file was generated using Mimey. DO NOT EDIT manually!
 *
 * @noinspection ALL
 * @codeCoverageIgnore
 */

declare(strict_types=1);

namespace TestMimeNamespace;

use RuntimeException;
use InvalidArgumentException;
use Elephox\Mimey\MimeTypeInterface;

enum TestMimeClass: string implements MimeTypeInterface
{
	case ApplicationJson = 'application/json';
	case ImageJpeg = 'image/jpeg';

	public function getExtension(): string
	{
		return match(\$this) {
			self::ApplicationJson => 'json',
			self::ImageJpeg => 'jpeg',

			default => throw new RuntimeException("Unknown extension for type: " . \$this->value),
		};
	}

	public function getValue(): string
	{
		return \$this->value;
	}

	public static function fromExtension(string \$extension): MimeType
	{
		\$type = self::tryFromExtension(\$extension);
		if (\$type === null) {
			throw new InvalidArgumentException("Unknown extension: " . \$extension);
		}

		return \$type;
	}

	public static function tryFromExtension(string \$extension): ?MimeType
	{
		return match(\$extension) {
			'json' => self::ApplicationJson,
			'jpeg' => self::ImageJpeg,
			'jpg' => self::ImageJpeg,

			default => null,
		};
	}
}

EOF, $phpEnum);
	}
}
