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

enum CstCommon: string
{
	case ALREADY_CANCELLED		= 'already_cancelled';
	case APPLICANT				= 'applicant';
	case APPLICANT_ID			= 'applicant_id';
	case APPLICANTID			= 'applicantId';
	case APPLICANTS				= 'applicants';
	case ARCHIVED				= 'archived';
	case BASENAME				= 'basename';
	case CODE					= 'code';
	case DATA					= 'data';
	case DELETED				= 'deleted';
	case DEV_NEXTCLOUD			= 'dev_nextcloud';
	case DOT					= '.';
	case EMAIL					= 'email';
	case ENVELOPEID				= 'session';
	case ERROR					= 'error';
	case EXCEPTION				= 'Exception';
	case FULLPATH				= 'fullpath';
	case GLOBAL_STATUS			= 'global_status';
	case ID						= 'id';
	case IDENTIFIER				= 'identifier';
	case ISSUER					= 'issuer';
	case ITEMS					= 'items';
	case LIST_ID				= 'listId';
	case MESSAGE				= 'message';
	case NAME					= 'name';
	case NEXTCLOUD				= 'nextcloud';
	case OWNER					= 'owner';
	case P7S					= 'p7s';
	case PDF					= 'pdf';
	case PENDINGACTIONS			= 'pendingActions';
	case RECIPIENT				= 'recipient';
	case RECIPIENTEMAIL			= 'recipientEmail';
	case RECIPIENTS				= 'recipients';
	case RESPONSE				= 'response';
	case RESULT					= 'result';
	case SIGNED					= 'signed';
	case SIMPLE					= 'simple';
	case STATUS					= 'status';
	case SUCCESS				= 'success';
	case URL_ARCHIVE			= '/storage/archive/';
	case USERID					= 'userId';
	case VALUE					= 'value';
	case VERSION				= 'version';
	case WF_SECRET				= 'WorkflowNotificationCallbackUrlSecretPreference';
}

enum CstConfig: string
{
	case SERVERS_NUMBER		= 'serversNumber';
}

enum CstDatabase: string
{
	case COLUMN_CLASS		= 'class';
	case COLUMN_LAST_RUN	= 'last_run';
	case COLUMN_RESERVED_AT	= 'reserved_at';
	case COUNT				= 'count';
	case QRY_UPDATED_ROWS	= 'updatedRows';
	case REQUESTS			= 'requests';
	case TABLE_JOBS			= 'jobs';
}

enum CstEntity: string
{
	case APPLICANT_ID		= 'applicant_id';
	case CHANGE_STATUS		= 'change_status';
	case EXPIRY_DATE		= 'expiry_date';
	case FILE_ID			= 'fileId';
	case GLOBAL_STATUS		= 'global_status';
	case MESSAGE			= 'message';
	case MUTEX				= 'mutex';
	case NAME				= 'name';
	case OVERWRITE			= 'overwrite';
	case RECIPIENT			= 'recipient';
	case SESSION			= 'session';
	case STATUS				= 'status';
	case WORKFLOW_ID		= 'workflow_id';
}

enum CstException: string
{
	case TYPE_NOT_FILE		= 'TypeNotFileException';
	case FILE_CREATION		= 'FileCreationException';
	case NOT_SERVERS_ARRAY	= 'NotServersArrayException';
}

enum CstFile: string
{
	case CONTENT			= 'content';
	case INTERNAL_PATH		= 'internalPath';
	case MTIME				= 'mtime';
	case NAME				= 'name';
	case PATH				= 'path';
	case SIZE				= 'size';
}

enum CstOOtpSign: string
{
	case ACTIONS			= 'actions';
	case CODE				= 'code';
	case COMMENT			= 'comment';
	case ERROR				= 'error';
	case FAULTCODE			= 'faultcode';
	case FAULTSTRING		= 'faultstring';
	case FILE				= 'file';
	case IDENTIFIER			= 'identifier';
	case MESSAGE			= 'message';
	case RECIPIENTEMAIL		= 'recipientEmail';
	case RESPONSE			= 'response';
	case RESULT				= 'result';
	case SENDERNAME			= 'senderName';
	case SESSION			= 'session';
	case STATUS				= 'status';
	case STATUSCODE			= 'statusCode';
	case STEPS				= 'steps';
}

enum CstRequest: string
{
	case APP_NAME			= 'appName';
	case CODE				= 'code';
	case COMMENT			= 'comment';
	case CURL_BODY			= 'CurlBody';
	case CURL_CODE			= 'curlCode';
	case DATA				= 'data';
	case ERROR				= 'error';
	case FILE				= 'file';
	case FULL_VERSION		= 'fullVersion';
	case FUNCTION_NAME		= 'functionName';
	case LIST				= 'list';
	case MESSAGE			= 'message';
	case NB_ITEMS			= 'nbItems';
	case NEW_VERSION		= 'newVersion';
	case PAGE				= 'page';
	case PROJEC_TPATH		= 'projectPath';
	case SOAP				= 'soap';
}

enum CstStatus: string
{
	case APPROVED			= 'approved';
	case CANCELLED			= 'cancelled';
	case DECLINED			= 'declined';
	case ERROR				= 'error';
	case EXPIRED			= 'expired';
	case ISSUE				= 'issue';
	case NOT_APPLICABLE		= 'not applicable';
	case NOT_FOUND			= 'not found';
	case NOT_STARTED		= 'not_started';
	case PENDING			= 'pending';
	case SIGNED				= 'signed';
	case STARTED			= 'started';
	case TO_BE_ARCHIVED		= 'to_be_archived';
}

class Constante
{
	static function config(CstConfig $config): string
	{
		return $config->value;
	}

	static function database(cstDatabase $database): string
	{
		return $database->value;
	}

	static function file(CstFile $file): string
	{
		return $file->value;
	}

	static function cst(CstCommon $cst): string
	{
		return $cst->value;
	}

	static function exception(CstException $exception): string
	{
		return $exception->value;
	}

	static function status(CstStatus $status): string
	{
		return $status->value;
	}

	static function entity(CstEntity $entity): string
	{
		return $entity->value;
	}

	static function ootpsign(CstOOtpSign $ootpsign): string
	{
		return $ootpsign->value;
	}

	static function request(CstRequest $request): string
	{
		return $request->value;
	}
}
