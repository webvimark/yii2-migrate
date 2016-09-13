<?php
namespace webvimark\migrate;

use yii\console\controllers\MigrateController;
use yii\db\Connection;
use yii\di\Instance;
use yii\helpers\ArrayHelper;
use Yii;

/**
 * This class automatically scan for migrations in all available modules.
 *
 * ```php
 *  'controllerMap' => [
 *      'migrate' => [
 *          'class' => 'webvimark\migrate\Controller',
 *          'configs' => [
 *              'config/console.php',
 *              'config/web.php',
 *          ],
 *      ],
 *  ],
 * ```
 * Class Controller
 * @package webvimark\migrate
 */
class Controller extends MigrateController
{
    /**
     * Configs with modules that will be scanned. Example
     *
     * ```php
     * 'configs' => [
     *      'config/console.php',
     *      'config/web.php',
     * ],
     * ```
     *
     * @var array
     */
    public $configs = [];
    /**
     * Scan for new migrations only in modules listed here
     *
     * @var array
     */
    public $onlyModules = [];
    /**
     * Do not scan for new migrations in modules listed here
     *
     * @var array
     */
    public $exceptModules = [];
    /**
     * You can specify additional paths. Example:
     *
     * ```php
     * 'additionalPaths' => [
     *      'some-path/some-dir', // directory
     *      '@yii/rbac/migrations', // directory with alias
     *      '@yii/web/migrations/m160313_153426_session_init.php', // single file
     * ],
     * ```
     *
     * @var array
     */
    public $additionalPaths = [];

    /**
     * Allow run migrations without @app/migrations folder
     *
     * @inheritdoc
     */
    public function beforeAction($action)
    {
        try {
            if (parent::beforeAction($action)) {
                return true;
            }
        } catch (\Exception $e) {
            $eMessage = "Directory specified in migrationPath doesn't exist";

            if ($e instanceof \yii\console\Exception && stripos($e->getMessage(), $eMessage) !== false) {
                if ($action->id !== 'create') {
                    $this->db = Instance::ensure($this->db, Connection::className());
                }
                $this->migrationPath = Yii::getAlias($this->migrationPath);

                $version = Yii::getVersion();
                $this->stdout("Yii Migration Tool (based on Yii v{$version})\n\n");
                return true;
            }

            throw $e;
        }

        return false;
    }

    /**
     * @inheritdoc
     * @since 2.0.8
     */
    public function optionAliases()
    {
        return array_merge(parent::optionAliases(), [
                'i' => 'interactive',
            ]);
    }

    /**
     * @inheritdoc
     */
    protected function getNewMigrations()
    {
        $migrations = $this->scanNewMigrations($this->migrationPath);

        foreach ($this->additionalPaths as $additionalPath) {
            $migrations = ArrayHelper::merge(
                $migrations,
                $this->scanNewMigrations(Yii::getAlias($additionalPath))
            );
        }

        $mergedConfig = [];

        foreach ($this->configs as $configFile) {
            $mergedConfig = ArrayHelper::merge($mergedConfig, require Yii::getAlias($configFile));
        }

        if ($mergedConfig && isset($mergedConfig['modules'])) {

            Yii::$app->setModules($mergedConfig['modules']);

            foreach (Yii::$app->getModules() as $moduleId => $data) {
                if (!empty($this->onlyModules) && !in_array($moduleId, $this->exceptModules)) {
                    continue;
                }

                if (in_array($moduleId, $this->exceptModules)) {
                    continue;
                }

                $migrations = ArrayHelper::merge(
                    $migrations,
                    $this->scanNewMigrations(Yii::$app->getModule($moduleId)->getBasePath() . '/migrations')
                );
            }
        }
        asort($migrations);

        // Do to use array_flip to exclude possible problem with similar names that wil became array keys
        foreach ($migrations as $path => &$name) {
            $name = $path;
        }

        return $migrations;
    }

    /**
     * Returns the migrations that are not applied.
     *
     * @param string $migrationPath
     *
     * @return array list of new migrations
     */
    protected function scanNewMigrations($migrationPath)
    {
        $applied = [];
        foreach ($this->getMigrationHistory(null) as $version => $time) {
            $applied[$version] = true;
        }

        $migrations = [];

        if (is_file($migrationPath)) {
            if (preg_match('/(m(\d{6}_\d{6})_.*?)\.php$/', $migrationPath, $matches)) {
                $this->prepareResult($migrations, $applied, $migrationPath, $matches[1]);
            }
            return $migrations;
        }

        if (!is_dir($migrationPath)) {
            return [];
        }
        $handle = opendir($migrationPath);
        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $path = $migrationPath . DIRECTORY_SEPARATOR . $file;
            if (preg_match('/^(m(\d{6}_\d{6})_.*?)\.php$/', $file, $matches) && is_file($path)) {
                $this->prepareResult($migrations, $applied, $path, $matches[1]);
            }
        }
        closedir($handle);

        return $migrations;
    }

    /**
     * @param array  $migrations
     * @param array  $applied
     * @param string $path
     * @param string $migrationName
     */
    protected function prepareResult(array &$migrations, $applied, $path, $migrationName)
    {
        if (strpos($path, Yii::$app->vendorPath) === 0) {
            $path = substr_replace($path, '@vendor', 0, strlen(Yii::$app->vendorPath));

        } elseif (strpos($path, Yii::$app->basePath) === 0) {
            $path = substr_replace($path, '@app', 0, strlen(Yii::$app->basePath));
        }

        $path = substr($path, 0, -4); // remove ".php"

        if (!isset($applied[$path])) {
            $migrations[$path] = $migrationName;
        }
    }

    /**
     * Creates a new migration instance.
     * @param string $class the migration class name
     * @return \yii\db\MigrationInterface the migration instance
     */
    protected function createMigration($class)
    {
        $file = Yii::getAlias($class . '.php');
        require_once($file);

        $parts = explode('/m', $class);

        $className = 'm' . end($parts);

        return new $className();
    }
}