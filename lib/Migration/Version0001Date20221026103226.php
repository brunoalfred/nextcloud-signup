<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2022 Bruno Alfred <hello@brunoalfred.me>
 *
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Twigacloudsignup\Migration;

use Closure;
use Doctrine\DBAL\Types\Types;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version0001Date20221026103226 extends SimpleMigrationStep
{
    /**
     * @param IOutput $output
     * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array $options
     * @return null|ISchemaWrapper
     */
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options)
    {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable('twigacloudsignup')) {
            $table = $schema->createTable('twigacloudsignup');
            $table->addColumn('id', Types::INTEGER, [
                'autoincrement' => true,
                'notnull' => true,
                'unsigned' => true,
            ]);
            $table->addColumn('phone', Types::STRING, [
                'notnull' => true,
            ]);
            $table->addColumn('username', Types::STRING, [
                'notnull' => false,
            ]);
            $table->addColumn('password', Types::STRING, [
                'notnull' => false,
            ]);
            $table->addColumn('displayname', Types::STRING, [
                'notnull' => false,
            ]);
            $table->addColumn('phone_confirmed', Types::BOOLEAN, [
                'notnull' => false,
                'default' => false,
            ]);
            $table->addColumn('token', Types::STRING, [
                'notnull' => true,
            ]);
            $table->addColumn('client_secret', Types::STRING, [
                'notnull' => false,
            ]);
            $table->addColumn('requested', Types::DATETIME_MUTABLE, [
                'notnull' => true,
            ]);
            $table->setPrimaryKey(['id']);
        }
        return $schema;
    }
}
