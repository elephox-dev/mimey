<?php

namespace Elephox\Mimey;

use JsonException;

/**
 * Generates a mapping for use in the MimeTypes class.
 *
 * Reads text in the format of httpd's mime.types and generates a PHP array containing the mappings.
 *
 * @psalm-type MimeTypeMap = array{mimes: array<non-empty-string, list<non-empty-string>>, extensions: array<non-empty-string, list<non-empty-string>>}
 */
class MimeMappingGenerator
{
	protected string $mime_types_text;
	protected ?array $map_cache = null;

	/**
	 * Create a new generator instance with the given mime.types text.
	 *
	 * @param non-empty-string $mime_types_text The text from the mime.types file.
	 */
	public function __construct(string $mime_types_text)
	{
		$this->mime_types_text = $mime_types_text;
	}

	/**
	 * Read the given mime.types text and return a mapping compatible with the MimeTypes class.
	 *
	 * @return MimeTypeMap The mapping.
	 */
	public function generateMapping(): array
	{
		if ($this->map_cache !== null) {
			return $this->map_cache;
		}

		$this->map_cache = [];
		$lines = explode("\n", $this->mime_types_text);
		foreach ($lines as $line) {
			$line = trim(preg_replace('~\\#.*~', '', $line));
			$parts = $line ? array_values(array_filter(explode("\t", $line))) : [];
			if (count($parts) === 2) {
				$mime = trim($parts[0]);
				$extensions = explode(' ', $parts[1]);
				foreach ($extensions as $extension) {
					$extension = trim($extension);
					if ($mime && $extension) {
						$this->map_cache['mimes'][$extension][] = $mime;
						$this->map_cache['extensions'][$mime][] = $extension;
						$this->map_cache['mimes'][$extension] = array_unique($this->map_cache['mimes'][$extension]);
						$this->map_cache['extensions'][$mime] = array_unique($this->map_cache['extensions'][$mime]);
					}
				}
			}
		}

		return $this->map_cache;
	}

	/**
	 * @return non-empty-string
	 * @throws JsonException
	 */
	public function generateJson(bool $minify = true): string
	{
		return json_encode($this->generateMapping(), JSON_THROW_ON_ERROR | ($minify ? 0 : JSON_PRETTY_PRINT));
	}

	/**
	 * @param non-empty-string $classname The name of the generated enum (defaults to: "MimeType")
	 * @param non-empty-string $namespace The namespace to put the enum into (defaults to this namespace)
	 * @param bool|null $withOverrideAttribute Whether to add the #[Override] attribute to the generated enum (requires PHP 8.3, defaults to true if PHP >= 8.3, false otherwise)
	 * @return non-empty-string
	 */
	public function generatePhpEnum(string $classname = "MimeType", string $namespace = __NAMESPACE__, ?bool $withOverrideAttribute = null): string
	{
		$usages = [];
		if ($namespace !== __NAMESPACE__) {
			$usages[] = "use " . MimeTypeInterface::class . ";";
		}

		$overrideAttribute = '';
		if (($withOverrideAttribute === null && PHP_VERSION_ID >= 80300) || $withOverrideAttribute) {
			$usages[] = "use Override;";
			$overrideAttribute = "\n\t#[Override]";
		}

		$values = [
			'namespace' => $namespace,
			'classname' => $classname,
			'usages' => implode("\n", $usages),
			'overrideAttribute' => $overrideAttribute,
			'cases' => "",
			'type2ext' => "",
			'ext2type' => "",
		];
		$stub = file_get_contents(dirname(__DIR__) . '/stubs/mimeType.php.stub');

		$mapping = $this->generateMapping();
		$nameMap = [];
		foreach ($mapping['extensions'] as $mime => $extensions) {
			$nameMap[$mime] = $this->convertMimeTypeToCaseName($mime);

			$values['cases'] .= sprintf("\tcase %s = '%s';\n", $nameMap[$mime], $mime);
			$values['type2ext'] .= sprintf("\t\t\tself::%s => '%s',\n", $nameMap[$mime], $extensions[0]);
		}

		foreach ($mapping['mimes'] as $extension => $mimes) {
			$values['ext2type'] .= sprintf("\t\t\t'%s' => self::%s,\n", $extension, $nameMap[$mimes[0]]);
		}

		foreach ($values as $name => $value) {
			$stub = str_replace("%$name%", $value, $stub);
		}

		return $stub;
	}

	protected function convertMimeTypeToCaseName(string $mimeType): string
	{
		return preg_replace('/([\/\-_+.]+)/', '', ucfirst(ucwords($mimeType, '/-_+.')));
	}
}
