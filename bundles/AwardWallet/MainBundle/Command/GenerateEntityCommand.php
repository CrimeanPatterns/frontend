<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

// See supported format in Version20151023142508 migration
// !!! Primary and foreign keys should be added manually
// It is possible that it could be replaced with vendor/doctrine/orm/lib/Doctrine/ORM/Tools/EntityGenerator.php
class GenerateEntityCommand extends Command
{
    protected $sqlPath;

    protected $savePath;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sqlQuery = file_get_contents($this->sqlPath);
        $status = preg_match('#CREATE\s+TABLE\s+`(.*?)`\s+\((.*)\)#is', $sqlQuery, $m);

        if (!$status) {
            $output->writeln('<error>ERROR: Invalid query format</error>');

            return 0;
        }
        $tableName = $m[1];
        $fieldsSrc = explode(",\n", $m[2]);
        $output->write('Creating fields array from SQL query... ');
        $fields = $this->createFieldsArray($fieldsSrc, $output);
        $output->writeln('DONE');

        $output->write('Creating header... ');
        $source = $this->createHeader($tableName);
        $output->writeln('DONE');

        $initialiasers = '';
        $fieldDeclarations = '';
        $gettersAndSetters = '';

        $output->write('Creating field declarations, getters, setters and initialisers for constructor... ');
        $fieldNames = [];

        foreach ($fields as $f) {
            $name = $f['name'];
            $nameLowered = lcfirst($name);
            $fieldNames[] = '$' . $nameLowered;
            [$type, $dbType] = $this->convertType($f['type']);
            $length = $f['length'];
            $nullable = var_export($f['nullable'], true);

            $ormDefinition = "name=\"$name\", type=\"$dbType\", nullable=$nullable";

            if ($length) {
                $ormDefinition .= ", length=$length";
            }

            $fieldDeclarations .= $this->createFieldDeclaration($nameLowered, $type, $ormDefinition);

            $getter = $this->createGetter($name, $nameLowered, $type);
            $setter = $this->createSetter($tableName, $name, $nameLowered, $type);
            $gettersAndSetters .= $getter . $setter;

            $initialiasers[] = "\t\t\$this->$nameLowered = \$$nameLowered;";
        }
        $output->writeln('DONE');

        $source .= $fieldDeclarations;

        $source .= $gettersAndSetters;

        array_shift($fieldNames);
        $constructorParameters = implode(', ', $fieldNames);

        array_shift($initialiasers);
        $initialiasers = implode("\n", $initialiasers);

        $output->write('Creating footer... ');
        $source .= $this->createFooter($constructorParameters, $initialiasers);
        $output->writeln('DONE');

        $output->writeln('Entity class seem to be fully generated');

        //		$output->writeln($source);
        $path = $this->savePath . '/' . $tableName . '.php';
        $output->write("Saving generated entity class to $path... ");
        file_put_contents($path, $source);
        $output->writeln('DONE');

        return 0;
    }

    protected function configure()
    {
        $this
            ->setName('aw:generate-entity')
            ->setDescription('Generate Doctrine entity by SQL "CREATE TABLE" statement')
            ->addOption(
                'load-query-from',
                'l',
                InputOption::VALUE_REQUIRED,
                'File to load SQL "CREATE TABLE" statement from'
            )
            ->addOption(
                'output-dir',
                'o',
                InputOption::VALUE_REQUIRED,
                'Path to save generated entity class'
            )
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $style = new OutputFormatterStyle('yellow');
        $output->getFormatter()->setStyle('warning', $style);
        $this->sqlPath = $input->getOption('load-query-from');
        $this->savePath = $input->getOption('output-dir');

        if (!$this->savePath) {
            $output->writeln('<error>ERROR: Output directory could not be empty</error>');

            exit;
        }
    }

    protected function createFieldsArray($fieldsSrc, $output)
    {
        $fields = [];

        foreach ($fieldsSrc as $f) {
            $f = preg_replace('#\s+#', ' ', trim($f));

            if (preg_match('#^`(.*?)`\s+(?:(\S+?)(?:\((\d+)\))?)\s+((?:NOT )?NULL)#i', $f, $m)) {
                $name = $m[1];
                $type = $m[2];
                $length = $m[3];

                switch ($m[4]) {
                    case 'NULL':
                        $nullable = true;

                        break;

                    case 'NOT NULL':
                        $nullable = false;

                        break;

                    default:
                        $output->writeln('<error>ERROR: Invalid null definition "' . $m[4] . '"</error>');

                        return;
                }
                $fields[] = [
                    'name' => $name,
                    'type' => $type,
                    'length' => $length,
                    'nullable' => $nullable,
                ];
            } else {
                $output->writeln('<warning>WARNING: Following string does not look like correct table field description and was skipped: "' . $f . '"</warning>');
            }
        }

        return $fields;
    }

    protected function createHeader($tableName)
    {
        return <<<HEADER
<?php

namespace AwardWallet\MainBundle\Entity;

use AwardWallet\MainBundle\Entity\Repositories\\{$tableName}Repository;
use Doctrine\ORM\Mapping as ORM;

/**
 * $tableName
 *
 * @ORM\Table(name="$tableName")
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\\{$tableName}Repository")
 */
class $tableName {

HEADER;
    }

    protected function convertType($srcType)
    {
        switch ($srcType) {
            // TODO: Fill with more types
            case 'INT':
                $type = 'integer';
                $dbType = 'integer';

                break;

            case 'VARCHAR':
                $type = 'string';
                $dbType = 'string';

                break;

            case 'DATETIME':
                $type = '\DateTime';
                $dbType = 'datetime';

                break;

            case 'FLOAT':
                $type = 'float';
                $dbType = 'float';

                break;

            default:
                $type = $srcType;
                $dbType = $srcType;
        }

        return [$type, $dbType];
    }

    protected function createFieldDeclaration($nameLowered, $type, $ormDefinition)
    {
        return <<<FIELD

	/**
	 * @var $type
	 *
	 * @ORM\Column($ormDefinition)
	 */
	protected \$$nameLowered;

FIELD;
    }

    protected function createGetter($name, $nameLowered, $type)
    {
        return <<<GETTER

	/**
	 * Get $nameLowered
	 *
	 * @return $type
	 */
	public function get$name() {
		return \$this->$nameLowered;
	}

GETTER;
    }

    protected function createSetter($tableName, $name, $nameLowered, $type)
    {
        return <<<SETTER

	/**
	 * Set $nameLowered
	 *
	 * @param $type \$$nameLowered
	 * @return $tableName
	 */
	public function set$name(\$$nameLowered) {
		\$this->$nameLowered = \$$nameLowered;
		return \$this;
	}

SETTER;
    }

    protected function createFooter($constructorParameters, $initialiasers)
    {
        return <<<FOOTER

	public function __construct($constructorParameters) {
$initialiasers
	}

}
FOOTER;
    }
}
