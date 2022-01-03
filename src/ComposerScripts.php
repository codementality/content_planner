<?php declare(strict_types=1);

namespace Fruition\Skeleton\Drupal;

use Composer\Installer\PackageEvent;
use Composer\Script\Event;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Yaml\Yaml;

/**
 * Contains command callbacks related to skeleton project operation.
 */
final class ComposerScripts {

  /**
   * Creates a unique UUID for the site.
   *
   * The Yaml operations in this method are intended to be broadly compatible
   * with symfony/yaml from versions 3-5 so as not to conflict with Drupal's
   * own dependency tree.
   *
   * @param \Composer\Script\Event $event
   */
  public static function createSiteUuid(Event $event): void {
    // Drupal is not yet instantiated to get the config sync directory, but
    // since this ships with our scaffolding we can reliably know the location.
    $systemConfig = dirname($event->getComposer()->getConfig()->get('vendor-dir')) . '/config/drupal/sync/system.site.yml';
    $value = Yaml::parseFile($systemConfig);
    $value['uuid'] = Uuid::v4()->toRfc4122();
    file_put_contents($systemConfig, Yaml::dump($value, 2, 2));
  }

  /**
   * Interactively customize the project.
   *
   * @param \Composer\Script\Event $event
   */
  public static function interactiveConfiguration(Event $event): void {
    $io = $event->getIO();

    $projectLabel = 'MyDrupal Project';
    $projectLabel = $io->ask("Project name [$projectLabel]: ", $projectLabel);
    $fruName = str_replace(' ', '-', strtolower($projectLabel));
    $fruName = $io->ask("Name for Fru tools [$fruName]: ", $fruName);
    $projectName = "$fruName/$fruName-drupal";
    $projectName = $io->ask("Name for composer.json (should match GitLab namespaced name) [$projectName]: ", $projectName);
    $dsName = "$fruName/$fruName-design";
    $dsName = $io->ask("URL for the project's design system [$dsName]: ", $dsName);
    $dsUrl = "https://git.fruition.net/$dsName";
    $dsUrl = $io->ask("URL for the project's design system [$dsUrl]: ", $dsUrl);

    $io->write([
      '',
      'You have entered:',
      "Project name: <warning>$projectLabel</warning>",
      "Name for Fru tools: <warning>$fruName</warning>",
      "Name for composer.json: <warning>$projectName</warning>",
      "URL for design system: <warning>$dsUrl</warning>",
    ]);
    if (!$io->askConfirmation('Is this correct (yes/no)? ')) {
      $io->writeError("<error>Aborted. To try again, run 'composer create-project' from the project directory.</error>");
    }

    $projectRoot = dirname($event->getComposer()->getConfig()->get('vendor-dir'));
    static::updateComposerJson("$projectRoot/composer.json", $projectName, $projectLabel, $dsName, $dsUrl);
    static::updateFruName("$projectRoot/.gitlab-ci.yml", $fruName);
    // Do not use {{fruname}} in the template file. DDev will reject it. Use
    // skeleton instead, and specify that as the token to be replaced.
    static::updateFruName("$projectRoot/.ddev/config.yaml", $fruName, 'skeleton');
    static::updateThemeFiles($projectRoot, $fruName, $projectLabel);
  }

  /**
   * Remind developers to export config and run updb prior to committing
   * when core/contrib are updated.
   *
   * @param \Composer\Installer\PackageEvent $event
   */
  public static function postPackageOperation(PackageEvent $event): void {
    /** @var \Composer\Package\PackageInterface $package */
    if (method_exists($event->getOperation(), 'getPackage')) {
      // Install or Uninstall
      $package = $event->getOperation()->getPackage();
    }
    else if (method_exists($event->getOperation(), 'getInitialPackage')) {
      // Update
      $package = $event->getOperation()->getInitialPackage();
    }
    else {
      throw new \RuntimeException(sprintf('Invalid use of %s', __CLASS__ . '::' . __METHOD__));
    }
    if (stripos($package->getName(), 'drupal/') !== 0) {
      return;
    }
    $io = $event->getIO();
    $io->write('  - <info>' . $event->getOperation()->__toString() . '. Drupal workflow notes:</info>');
    $io->write([
      "\tAfter installing, uninstalling or upgrading Drupal core/modules,",
      "\trun database updates & export config. Commit changes alongside the",
      "\tupdated composer.{lock,json} files.",
    ]);
  }

  /**
   * Update composer.json with the project name.
   *
   * After calling this function, the post-create-project command updates the
   * lock file.
   *
   * @param string $composerJsonFile
   *   The full path to composer.json.
   * @param string $projectName
   *   The project name, such as drupal/skeleton.
   * @param string $projectLabel
   *   The project label, such as DrupalSkeleton.
   * @param string $dsName
   *   The package name of the project's design system.
   * @param string $dsUrl
   *   The URL for the project's design system.
   */
  protected static function updateComposerJson(string $composerJsonFile, string $projectName, string $projectLabel, string $dsName, string $dsUrl): void {
    $composerJson = json_decode(file_get_contents($composerJsonFile));
    $composerJson->name = $projectName;
    $composerJson->description = "$projectLabel site using Drupal 9.";
    $composerJson->repositories->design->url = $dsUrl;
    unset($composerJson->require->{'skeleton/design-skeleton'});
    $composerJson->require->{$dsName} = 'dev-main';
    file_put_contents(
      $composerJsonFile,
      json_encode(
        $composerJson,
        JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES
      ) . PHP_EOL
    );
  }

  /**
   * Update a file with the Fru tools project name.
   *
   * Do not use YAML parsing, since that will remove all comments.
   *
   * @param string $fileName
   *   The full path to the file to be updated.
   * @param string $fruName
   *   The project name for Fru tools, such as drupal-skeleton.
   * @param string $token
   *   (optional) The token to be replaced. Defaults to '{{fruname}}'.
   */
  protected static function updateFruName(string $fileName, string $fruName, string $token = '{{fruname}}'): void {
    $file = file_get_contents($fileName);
    $file = str_replace($token, $fruName, $file);
    file_put_contents($fileName, $file);
  }

  /**
   * Update the theme files with the project name.
   *
   * @param string $projectRoot
   *   The full path to the project root directory.
   * @param string $fruName
   *   The project name for Fru tools, such as drupal-skeleton.
   * @param string $projectLabel
   *   The project label, such as DrupalSkeleton.
   */
  protected static function updateThemeFiles(string $projectRoot, string $fruName, string $projectLabel): void {
    $fruName = str_replace('-', '_', $fruName);
    $themeDir = "$projectRoot/web/themes/custom/fruition_theme";
    $schemaDir = "$themeDir/config/schema";
    mkdir(str_replace('fruition', $fruName, $schemaDir), 0777, true);
    $files = array_merge(glob("$themeDir/*.*"), glob("$schemaDir/*.*"));

    foreach ($files as $file) {
      if (!preg_match('/\.(png|svg|jpg|jpeg)$/i', $file)) {
        // Replace 'fruition' and 'Fruition' with $fruName and $projectLabel.
        $text = file_get_contents($file);
        $text = str_replace('fruition', $fruName, $text);
        $text = str_replace('Fruition', $projectLabel, $text);
        file_put_contents($file, $text);
      }
      rename($file, str_replace('fruition', $fruName, $file));
    }

    // The original directories should now be empty. If not, then give me an
    // error message!
    rmdir($schemaDir);
    rmdir("$themeDir/config");
    rmdir($themeDir);
  }

}
