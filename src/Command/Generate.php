<?php

namespace Codeception\Module\QaApi\Command;

use \Codeception\Configuration;
use \Symfony\Component\Console\Command\Command;
use \Codeception\CustomCommandInterface;
use \Symfony\Component\Console\Input\InputOption;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;
use \Codeception\Module\QaApi\Generator\Helper;

class Generate extends Command implements CustomCommandInterface
{
    use \Codeception\Command\Shared\FileSystem;
    use \Codeception\Command\Shared\Config;

    public static function getCommandName()
    {
        return "qaapi:generate";
    }

    protected function configure()
    {
        $this->setDefinition(array(
            new InputOption('with-sample', 's', InputOption::VALUE_NONE, 'Generate a sample qaapi.json if it\'s not exist'),
        ));

        parent::configure();
    }

    public function getDescription()
    {
        return "Generates QaApi module and client";
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $qaapiFile = codecept_root_dir('qaapi.json');
        $json = file_get_contents($qaapiFile);
        if (!$json) {
            if ($input->getOption('with-sample')) {
                $json = file_get_contents('http://petstore.swagger.io/v2/swagger.json');
                if (!$json) {
                    $output->writeln('<error>Can\'t get a sample qaapi.json file</error>');
                    return;
                }

                $res = file_put_contents($qaapiFile, $json);
                if (!$res) {
                    $output->writeln('<error>Can\'t save a sample qaapi.json file</error>');
                    return;
                }

                $output->writeln('Sample qaapi.json file was saved');
            } else {
                $output->writeln('<error>"qaapi.json" file was not found in a project directory. Use "-s" flag to get a sample "qaapi.json" file.</error>');
                return;
            }
        }

        $config = $this->getGlobalConfig($input->getOption('config'));
        $namespace = (!empty($config['namespace']) ? $config['namespace'] : '');

        // generate QaApi files
        $res = $this->_generateQaApi($json, $namespace, $output);
        if (!$res) {
            return;
        }

        // generate helper
        $name = 'QaApi';
        $path = $this->buildPath(Configuration::supportDir() . 'Helper', $name);
        $filename = $path . $this->getClassName($name) . '.php';
        if (!file_exists($filename)) {
            $res = $this->save($filename, (new Helper($name, $config['namespace']))->produce());
            if ($res) {
                $output->writeln('<info>Helper ' . $filename . ' created</info>');
            } else {
                $output->writeln('<error>Error creating helper ' . $filename . '</error>');
            }
        }
    }

    private function _generateQaApi($json, $namespace, OutputInterface $output)
    {
        $url = 'http://qadept.com/system/generateQaApi';

        $fields = [
            'json' => base64_encode($json),
            'namespace' => $namespace,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $output->writeln('Please wait, files are being generated...');

        $res = curl_exec($ch);
        curl_close($ch);

        if (!$res) {
            $output->writeln('<error>Can\'t send a request to generate files. CURL error: "' . curl_error($ch) . '".</error>');
            return false;
        }

        $res = json_decode($res, true);
        if (!$res || !isset($res['url'])) {
            $error = (!empty($res['message']) ? $res['message'] : 'Unknown error');
            $output->writeln('<error>Files were not generated. Error: "' . $error . '".</error>');
            return false;
        }

        $resource = fopen($res['url'], 'r');
        if (!$resource) {
            $output->writeln('<error>Can\'t download archive with generated files (URL: ' . $res['url'] . ').</error>');
            return false;
        }

        $generatedPath = $this->buildPath(Configuration::supportDir(), '_generated/.');
        $archiveFile = $generatedPath . 'qaapi.zip';
        $res = file_put_contents($archiveFile, $resource);
        if (!$res) {
            $output->writeln('<error>Can\'t save archive with generated files (URL: ' . $res['url'] . ').</error>');
            return false;
        }

        $zip = new \ZipArchive();
        if ($zip->open($archiveFile) !== true) {
            $output->writeln('<error>Can\'t open downloaded archive.</error>');
            return false;
        }

        $zip->extractTo($generatedPath);
        $zip->close();

        unlink($archiveFile);

        $output->writeln('<info>QaApi files was successfully generated and saved in "' . $generatedPath . '" directory.');

        return true;
    }
}