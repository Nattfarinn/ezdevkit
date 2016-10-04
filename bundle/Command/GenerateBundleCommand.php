<?php

namespace EzSystems\DevkitBundle\Command;

use EzSystems\Devkit\Generator\BundleGenerator;
use Sensio\Bundle\GeneratorBundle\Command\GeneratorCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class GenerateBundleCommand extends GeneratorCommand
{
    const NAME = 'devkit:generate:bundle';

    protected function configure()
    {
        $this
            ->setDefinition([
                new InputOption('namespace', '', InputOption::VALUE_REQUIRED, 'The base namespace of the bundle to create, without bundle itself'),
                new InputOption('bundle-name', '', InputOption::VALUE_REQUIRED, 'Bundle name, without "Bundle" suffix'),
                new InputOption('example', '', InputOption::VALUE_REQUIRED, 'Prepare example boilerplate code'),
            ])
            ->setDescription('Generates a bundle')
            ->setHelp(sprintf(<<<EOT
The <info>%s</info> command helps you generates new bundles.

By default, the command interacts with the developer to tweak the generation.
Any passed option will be used as a default value for the interaction.

Note that you can use <comment>/</comment> instead of <comment>\\ </comment>for the namespace delimiter to avoid any
problem.

If you want to disable any user interaction, use <comment>--no-interaction</comment> but don't forget to pass all needed options:

<info>php app/console %s --namespace=EzSystems --bundle-name=AwesomeFeature --no-interaction</info>

Note that the bundle namespace and name <info>must not</info> end with "Bundle". It's added automatically wherever it's required.
EOT
            , self::NAME, self::NAME, self::NAME))
            ->setName(self::NAME)
        ;
    }

    protected function getSkeletonDirs(BundleInterface $bundle = null)
    {
        return [
             __DIR__.'/../Resources/skeleton'
        ];
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $questionHelper = $this->getQuestionHelper();

        if ($input->isInteractive()) {
            if (!$questionHelper->ask($input, $output, new ConfirmationQuestion($questionHelper->getQuestion("\nDo you confirm generation", 'yes', '?'), true))) {
                $output->writeln('<error>Command aborted</error>');

                return 1;
            }
        }

        foreach (array('namespace', 'bundle-name', 'example') as $option) {
            if (null === $input->getOption($option)) {
                throw new \RuntimeException(sprintf('The "%s" option must be provided.', $option));
            }
        }

        $namespace = Validators::validateBundleNamespace($input->getOption('namespace'));
        $bundle = Validators::validateBundleName($input->getOption('bundle-name'));
        $example = Validators::validateBundleName($input->getOption('example'));

        $generator = $this->getGenerator();
        $generator->generate($namespace, $bundle, $example == 'yes');

        $output->writeln('Generating the bundle code: <info>OK</info>');

        $errors = [];

        $questionHelper->writeGeneratorSummary($output, $errors);
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $questionHelper = $this->getQuestionHelper();
        $questionHelper->writeSection($output, 'Welcome to the eZ Systems bundle generator');

        // namespace
        $namespace = null;
        try {
            // validate the namespace option (if any) but don't require the vendor namespace
            $namespace = Validators::validateBundleNamespace($input->getOption('namespace'));
        } catch (\Exception $error) {
            $output->writeln($questionHelper->getHelperSet()->get('formatter')->formatBlock($error->getMessage(), 'error'));
        }

        if (null === $namespace) {
            $output->writeln([
                '',
                'Your application code must be written in <comment>bundles</comment>. This command helps',
                'you generate them easily.',
                '',
                'Each bundle is hosted under a namespace (like <comment>Acme/Bundle/BlogBundle</comment>).',
                'The namespace should begin with a "vendor" name like your company name, your',
                'project name, or your client name and may be followed by one or more optional category',
                'sub-namespaces, and it <info>should not</info> end with the bundle name itself',
                '(this one is generated automatically from bundle name parameter).',
                '',
                'Use <comment>/</comment> instead of <comment>\\ </comment> for the namespace delimiter to avoid any problem.',
                '',
            ]);

            $question = new Question($questionHelper->getQuestion('Bundle namespace', $input->getOption('namespace')), $input->getOption('namespace'));
            $question->setValidator(function ($answer) {
                return Validators::validateBundleNamespace($answer);
            });
            $namespace = $questionHelper->ask($input, $output, $question);

            $input->setOption('namespace', $namespace);
        }

        // bundle name
        $bundle = null;
        try {
            $bundle = Validators::validateBundleName($input->getOption('bundle-name'));
        } catch (\Exception $error) {
            $output->writeln($questionHelper->getHelperSet()->get('formatter')->formatBlock($error->getMessage(), 'error'));
        }

        if (null === $bundle) {
            $output->writeln([
                '',
                'In your code, a bundle is often referenced by its name. It can be the',
                'concatenation of all namespace parts but it\'s really up to you to come',
                'up with a unique name.',
                '',
            ]);
            $question = new Question($questionHelper->getQuestion('Bundle name', $bundle), $bundle);
            $question->setValidator(function ($answer) {
                return Validators::validateBundleName($answer);
            });
            $bundle = $questionHelper->ask($input, $output, $question);
            $input->setOption('bundle-name', $bundle);
        }

        // example
        $example = null;
        try {
            $example = Validators::validateExample($input->getOption('example'));
        } catch (\Exception $error) {
            $output->writeln($questionHelper->getHelperSet()->get('formatter')->formatBlock($error->getMessage(), 'error'));
        }

        if (null === $bundle) {
            $output->writeln([
                '',
                'Generator has some basic example boilerplate code ready. Dummy controller',
                'action connected to service and so on. It does nothing fancy, just shows',
                'how to do stuff like. It\'s not required and you\'re free to ignore it.',
                '',
            ]);
            $question = new Question($questionHelper->getQuestion('Do you want example code?', 'no'), $example);
            $question->setValidator(function ($answer) {
                return Validators::validateExample($answer);
            });
            $example = $questionHelper->ask($input, $output, $question);
            $input->setOption('example', $example);
        }
    }

    protected function createGenerator()
    {
        return new BundleGenerator($this->getContainer()->get('filesystem'));
    }
}