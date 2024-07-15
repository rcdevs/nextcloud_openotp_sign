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

namespace OCA\OpenOTPSign;

use ArrayObject;
use nusoap_client;
use OCA\OpenOTPSign\Utils\Constante;
use OCA\OpenOTPSign\Utils\CstOOtpSign;
use OCA\OpenOTPSign\Utils\CstRequest;
use OCA\OpenOTPSign\Utils\Helpers;

class OOtpResponse extends ArrayObject
{
	private array $array;

	function __construct(array|bool $input)
	{
		switch (true) {
			case is_array($input):
				parent::__construct($input, ArrayObject::ARRAY_AS_PROPS);
				$this->array = $input;
				$this->array[Constante::ootpsign(CstOOtpSign::CODE)] = intval($this->array[Constante::ootpsign(CstOOtpSign::CODE)]);
				break;

			case is_bool($input) && !$input:
				$this->array[Constante::ootpsign(CstOOtpSign::CODE)] = 0;
				$this->array[Constante::ootpsign(CstOOtpSign::MESSAGE)] = 'Nusoap retuned a false';
				break;

			default:
				$this->array[Constante::ootpsign(CstOOtpSign::CODE)] = 0;
				$this->array[Constante::ootpsign(CstOOtpSign::MESSAGE)] = 'Nusoap retuned an unexpected response';
				break;
		}
	}

	public function getArray(): array
	{
		return $this->array;
	}

	public function getCode(): int|null
	{
		return Helpers::getIfExists(Constante::ootpsign(CstOOtpSign::CODE), $this->array, returnNull: true);
	}

	public function getComment(): string|null
	{
		return Helpers::getIfExists(Constante::ootpsign(CstOOtpSign::COMMENT), $this->array, returnNull: true);
	}

	public function getData(): string|null
	{
		$data = Helpers::getIfExists(Constante::request(CstRequest::DATA), $this->array, returnNull: true);
		if (is_null($data)) {
			$data = [];
		}
		return $data;
	}

	public function setData(array $data)
	{
		$this->array[Constante::request(CstRequest::DATA)] = $data;
	}

	public function getError(): string|null
	{
		return Helpers::getIfExists(Constante::ootpsign(CstOOtpSign::ERROR), $this->array, returnNull: true);
	}

	public function getFaultcode(): string|null
	{
		return Helpers::getIfExists(Constante::ootpsign(CstOOtpSign::FAULTCODE), $this->array, returnNull: true);
	}

	public function getFaultstring(): string|null
	{
		return Helpers::getIfExists(Constante::ootpsign(CstOOtpSign::FAULTSTRING), $this->array, returnNull: true);
	}

	public function getFile(): string|null
	{
		return Helpers::getIfExists(Constante::ootpsign(CstOOtpSign::FILE), $this->array, returnNull: true);
	}

	public function getMessage(): string|null
	{
		return Helpers::getIfExists(Constante::ootpsign(CstOOtpSign::MESSAGE), $this->array, returnNull: true);
	}

	public function getSession(): string|null
	{
		return Helpers::getIfExists(Constante::ootpsign(CstOOtpSign::SESSION), $this->array, returnNull: true);
	}

	public function getSoap(): nusoap_client
	{
		return $this->array[Constante::request(CstRequest::DATA)][Constante::request(CstRequest::SOAP)];
	}

	public function setSoap(nusoap_client $soap)
	{
		// Check id DATA exists
		if (is_null(Helpers::getIfExists(Constante::request(CstRequest::DATA), $this->array, returnNull: true))) {
			$this->setData([]);
		}
		$this->array[Constante::request(CstRequest::DATA)][Constante::request(CstRequest::SOAP)] = $soap;
	}

	public function isCode(int $value): bool
	{
		return ($this->getCode() === $value);
	}

	public function isFailed(): bool
	{
		return ($this->getCode() === 0);
	}
}
