<?php

/**
 *
 * @copyright Copyright (c) 2024, RCDevs (info@rcdevs.com)
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace OCA\OpenOTPSign\Utils;

use Exception;
use OCP\IL10N;

class Helpers
{
	public static IL10N $l;

	public static function getArrayData(array|null $array, string $key, bool $missingForbidden, string $exceptionMessage = null)
	{
		if (is_null($array) && $missingForbidden) throw new Exception(self::$l->t("Array is null"), 1);

		if (is_null($array) && !$missingForbidden) return '';

		if (array_key_exists($key, $array)) {
			return $array[$key];
		} else {
			if ($missingForbidden) {
				$exceptionMessage = $exceptionMessage ?? "Missing key \"{$key}\" in array";
				throw new Exception(self::$l->t($exceptionMessage), 1);
			} else {
				return '';
			}
		}
	}

	public static function getIfExists(string $field, array|object $requestIntel, bool $returnNull = true)
	{
		try {
			$returnValue = ($returnNull ? null : '');

			switch (true) {
				case is_array($requestIntel):

					if (array_key_exists($field, $requestIntel)) {
						return $requestIntel[$field];
					} else {
						return $returnValue;
					}
					break;

				case is_object($requestIntel):
					foreach ($requestIntel as $key => $value) {
						if ($key === $field) {
							return $value;
							break;
						}
					}
					return $returnValue; //Not found here...
					break;

				default:
					return $returnValue;
					break;
			}
		} catch (\Throwable $th) {
			return $returnValue;
		}
	}

	public static function humanFileSize(int $size, string $unit = "")
	{
		if ((!$unit && $size >= 1 << 30) || $unit == "GB")
			return number_format($size / (1 << 30), 2) . " GB";

		if ((!$unit && $size >= 1 << 20) || $unit == "MB")
			return number_format($size / (1 << 20), 2) . " MB";

		if ((!$unit && $size >= 1 << 10) || $unit == "KB")
			return number_format($size / (1 << 10), 2) . " KB";

		return number_format($size) . " bytes";
	}

	public static function isAdvanced(int $advanced)
	{
		try {
			return (intval($advanced) === 1);
		} catch (\Throwable $th) {
			throw $th;
		}
	}

	public static function isValidResponse(array $response)
	{
		try {
			$code = intval($response[Constante::ootpsign(CstOOtpSign::CODE)]);
			return ($code === 1);
		} catch (\Throwable $th) {
			throw $th;
		}
	}

	public static function isPdf(string $path)
	{
		try {
			return
				strcasecmp(
					pathinfo($path, PATHINFO_EXTENSION),
					Constante::cst(CstCommon::PDF)
				) === 0;
		} catch (\Throwable $th) {
			throw $th;
		}
	}

	public static function warning(string $warningMsg)
	{
		return ['code' => false, 'message' => self::$l->t($warningMsg)];
	}
}
