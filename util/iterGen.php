<?php
require __DIR__ . '/../web/kernel/public.php';

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\ParserFactory;

function extractFunctionsFromFile(string $file, string $namespace) : \Iterator
{
    $parser = (new ParserFactory)->create(ParserFactory::ONLY_PHP7);
    $ast = $parser->parse(file_get_contents($file));

    yield from it($ast)
        ->filterIsInstance(Namespace_::class)
        ->filterByField('name', $namespace)
        ->flatMapField('stmts')
        ->filterIsInstance(Function_::class)
        ->filterByPropertyPath('returnType', 'Iterator');
}

$iter =
    it(extractFunctionsFromFile(
        '/www/awardwallet/vendor/nikic/iter/web/iter.php',
        'iter'
    ))
    ->filter(function (Function_ $function) {
        return !in_array($function->name, [
            'toIter',
            'zip',
            'slice',
            'take',
            'drop'
        ]);
    })
    ->toPairsWithSecond('\\iter');

$iterAux =
    it(extractFunctionsFromFile(
        $fileName = '/www/awardwallet/bundles/AwardWallet/MainBundle/Globals/Utils/iter.php',
        'AwardWallet\\MainBundle\\Globals\\Utils\\iter'
    ))
    ->filter(function (Function_ $function) {
        return !in_array($function->name, [
            'mapByTrim',
            'drain',
            'toIterInternal',
            'toGenerator',
            'makeUnrewindable',
            'iteratorSequence',
            'chunkLazy',
            'groupLazyBy',
            'groupLazyByColumn',
            'memoize',
        ]);
    })
    ->toPairsWithSecond('iterAux');

$counter = 0;
$generated =
    it($iter)
    ->chain($iterAux)
    ->increment($counter)
    ->map(function ($pair) : string {
        /** @var Function_ $function */
        [$function, $namespacePrefix] = $pair;
        $outerArgsList =
            it($function->params)
            ->map(function (Param $param) : string {
                $paramCode = '';

                if ($param->type instanceof \PhpParser\Node\NullableType) {
                    $paramCode = '?' . $param->type->type . ' ';
                } elseif ($param->type !== null) {
                    $paramCode = $param->type . ' ';
                }

                if ($param->byRef) {
                    $paramCode .= '& ';
                }

                $paramCode .= ($param->variadic ? '...' : '') . '$' . $param->name;

                if (isset($param->default)) {
                    $default = $param->default;

                    if (method_exists($default, '__toString')) {
                        $paramCode .= ' = ' . $default;
                    } elseif ($default instanceof \PhpParser\Node\Scalar\LNumber) {
                        $paramCode .= ' = ' . $default->value;
                    } elseif ($default instanceof \PhpParser\Node\Scalar\String_) {
                        $paramCode .= ' = ' . json_encode($default->value);
                    } elseif ($default instanceof \PhpParser\Node\Expr\ConstFetch) {
                        $paramCode .= ' = ' . $default->name;
                    } else {
                        throw new \RuntimeException('Unknown default value');
                    }
                }

                return $paramCode;
            })
            ->joinToString(', ');

        $innerArgsList =
            it($function->params)
            ->map(function (Param $param) : string {
                $code = '';

                if ($param->variadic) {
                    $code .= '...';
                }

                $code .= '$' . $param->name;

                return $code;
            })
            ->joinToString(', ');

        $code  = "    function {$function->name}($outerArgsList) : IteratorFluent\n";
        $code .= "    {\n";
        $code .= "        return new IteratorFluent({$namespacePrefix}\\{$function->name}($innerArgsList));\n";
        $code .= "    }\n";

        return $code;
    })
    ->joinToString("\n");

file_put_contents(
    $fileName,
    preg_replace(
        "/# GENERATED\(util\/iterGen\.php\) BEGIN\n.*# GENERATED END\n/ms",
        "# GENERATED(util/iterGen.php) BEGIN\n\n{$generated}\n# GENERATED END\n",
        file_get_contents($fileName)
    )
);

echo "Generated {$counter} function(s)\n";
