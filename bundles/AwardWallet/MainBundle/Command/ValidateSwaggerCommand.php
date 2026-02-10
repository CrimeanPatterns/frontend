<?php

namespace AwardWallet\MainBundle\Command;

use AwardWallet\MainBundle\FrameworkExtension\Command;
use JsonSchema\RefResolver;
use JsonSchema\Uri\UriResolver;
use JsonSchema\Uri\UriRetriever;
use JsonSchema\Validator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class ValidateSwaggerCommand extends Command
{
    protected static $defaultName = 'aw:validate-swagger';

    public function configure()
    {
        $this
            ->setDescription("validate json data against swagger schema")
            ->addArgument('schema', InputArgument::REQUIRED, 'swagger-schema.yml')
            ->addArgument('data', InputArgument::REQUIRED, 'data.json')
            ->addArgument('path', InputArgument::REQUIRED, 'path in swagger-schema.yml, like "/paths/~1member/get/responses/200/schema". Replace / with ~1 in path components');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $schema = Yaml::parse(file_get_contents($input->getArgument('schema')));
        $jsonSchema = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "/swagger-schema.json";
        file_put_contents($jsonSchema, json_encode($schema, JSON_PRETTY_PRINT));

        $path = $input->getArgument('path');

        if (!preg_match("#^(/\w+)+#ims", $path)) {
            throw new \InvalidArgumentException("Path should starts with /. Do not include trailing /");
        }

        $data = json_decode(file_get_contents($input->getArgument('data')));

        $retriever = new UriRetriever();
        $uriResolver = new UriResolver();
        $refResolver = new RefResolver($retriever, $uriResolver);
        $schemaAtPath = $refResolver->resolve('file://' . $jsonSchema . '#' . $path);

        $validator = new Validator();
        $validator->check($data, $schemaAtPath);

        if ($validator->isValid()) {
            $output->writeln("<info>Data is valid</info>");
        } else {
            $errors = $validator->getErrors();
            $output->writeln(json_encode($errors, JSON_PRETTY_PRINT));
            $output->writeln("<error>" . count($errors) . " errors detected!</error>");
        }

        return 0;
    }
}
