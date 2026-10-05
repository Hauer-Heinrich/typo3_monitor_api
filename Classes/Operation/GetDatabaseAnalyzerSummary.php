<?php
declare(strict_types=1);

namespace HauerHeinrich\Typo3MonitorApi\Operation;

/**
 * This file is part of the "typo3_monitor_api" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * Edited by www.hauer-heinrich.de
 * @author
 */

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Database\Schema\SqlReader;
use TYPO3\CMS\Core\Database\Schema\SchemaMigrator;
use TYPO3\CMS\Core\Database\Schema\Exception\StatementException;
use HauerHeinrich\Typo3MonitorApi\OperationResult;

/**
 * Information about database schema updates.
 */
class GetDatabaseAnalyzerSummary implements IOperation {
    public function __construct(
        private readonly SqlReader $sqlReader,
        private readonly SchemaMigrator $schemaMigrator,
    ) {}

    /**
     * @param array $parameter None
     * @return OperationResult the current application context
     */
    public function execute(array $parameter, ServerRequestInterface $request): OperationResult {
        try {
            $values = [];
            $sqlStatements = $this->sqlReader->getCreateTableStatementArray($this->sqlReader->getTablesDefinitionString());
            $schemaMigrationService = $this->schemaMigrator;
            $addCreateChange = $schemaMigrationService->getUpdateSuggestions($sqlStatements);
            $addCreateChange = array_merge_recursive(...array_values($addCreateChange));
            if (!empty($addCreateChange['add'])) {
                $values[] = 'NewField';
            }
            if (!empty($addCreateChange['create_table'])) {
                $values[] = 'NewTable';
            }
            if (!empty($addCreateChange['change'])) {
                $values[] = 'ChangedField';
            }
            if (!empty($addCreateChange['change_currentValue'])) {
                $values[] = 'ChangedTable';
            }

            // Difference from current to expected
            $dropRename = $schemaMigrationService->getUpdateSuggestions($sqlStatements, true);
            $dropRename = array_merge_recursive(...array_values($dropRename));
            if (!empty($dropRename['change'])) {
                $values[] = 'UnusedField';
            }
            if (!empty($dropRename['change_table'])) {
                $values[] = 'UnusedTable';
            }
            if (!empty($dropRename['drop'])) {
                $values[] = 'DropTable';
            }
            if (!empty($dropRename['drop_table'])) {
                $values[] = 'DropField';
            }

            return new OperationResult(true, [[ 'data' => implode(',', $values) ]]);

        } catch (StatementException $e) {
            // Ignore
        }

        // TODO: error message
        return new OperationResult(false, [], '');
    }
}
