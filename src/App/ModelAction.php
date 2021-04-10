<?php

namespace Protoqol\Prequel\App;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Artisan;
use Protoqol\Prequel\Interfaces\GenerationInterface;
use Protoqol\Prequel\Traits\classResolver;

class ModelAction implements GenerationInterface
{
    use classResolver;

    /**
     * @var string $database
     */
    private $database;

    /**
     * @var string $table
     */
    private $table;

    /**
     * ControllerAction constructor.
     *
     * @param string $database
     * @param string $table
     */
    public function __construct(string $database, string $table)
    {
        $this->database = $database;
        $this->table = $table;
    }

    /**
     * Generate
     *
     * @return mixed
     */
    public function generate()
    {
        Artisan::call("make:model", [
            "name" => $this->generateModelName($this->table),
        ]);

        $this->setActualTable(
            $this->table,
            (bool)($this->configNamespaceResolver("model")->suffix === "")
        );

        $this->dumpAutoload();

        return (string)$this->getQualifiedName();
    }

    /**
     * If user has set a suffix for their models this method will add a protected $table attribute to their model.
     * This will also a timestamp and Prequel signature at the top of every model generated by Prequel.
     *
     * @param string $table
     * @param bool $onlyMark
     *
     * @return bool|int
     */
    public function setActualTable(string $table, bool $onlyMark = false)
    {
        // Get path for Model
        $path = base_path("app/" . $this->generateModelName($table) . ".php");
        $path = preg_replace("~[\\\/]~", DIRECTORY_SEPARATOR, $path);
        $content = file_get_contents($path);

        if ($onlyMark) {
            // Replacement attribute, kindly do not touch! :)
            $comment =
                "// Better model organisation and an automatic \$table attribute setter, this saved you at least 2 seconds! - Prequel";
            $replacement =
                $comment . "\n    protected \$table = '" . $table . "';";

            // Replace the double slashes (//) in the newly created model with an attribute for the actual table name.
            $content = preg_replace("@//@", $replacement, $content);
        }

        // Stamp
        $stamp = "<?php\n\n// Generated by Prequel @" . (string)Carbon::now();
        $content = preg_replace("@<\?php@", $stamp, $content);

        return file_put_contents($path, $content);
    }

    /**
     * STUB
     */
    public function setActualConnection()
    {
        // STUB
    }

    /**
     * Get fully qualified class name
     *
     * @return mixed
     */
    public function getQualifiedName()
    {
        try {
            return $this->generateModelName($this->table);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get class name
     *
     * @return mixed
     */
    public function getClassname()
    {
        $class = $this->getQualifiedName();

        if (!$class) {
            return false;
        }

        $arr = explode("\\", $class);
        $count = count($arr);

        return $arr[$count - 1];
    }

    /**
     * Get class namespace
     *
     * @return mixed
     */
    public function getNamespace()
    {
        if (!$this->getQualifiedName()) {
            return false;
        }

        $arr = explode("\\", $this->getQualifiedName());
        $count = count($arr);
        $namespace = "";

        for ($i = 0; $i < $count; $i++) {
            if ($i === $count - 1) {
                break;
            }
            $namespace .= (string)$arr[$i] . "\\";
        }

        return $namespace;
    }
}