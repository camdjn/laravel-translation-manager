<?php namespace camdjn\TranslationManager;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Events\Dispatcher;
use camdjn\TranslationManager\Models\Translation;
use camdjn\TranslationManager\Models\Group;
use camdjn\TranslationManager\Models\Locale;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Finder\Finder;

class Manager{

    /** @var \Illuminate\Foundation\Application  */
    protected $app;
    /** @var \Illuminate\Filesystem\Filesystem  */
    protected $files;
    /** @var \Illuminate\Events\Dispatcher  */
    protected $events;

    protected $config;

    public function __construct(Application $app, Filesystem $files, Dispatcher $events)
    {
        $this->app = $app;
        $this->files = $files;
        $this->events = $events;
        $this->config = $app['config']['translation-manager'];
    }

    public function missingKey($namespace, $group, $key)
    {
        if(!in_array($group, $this->config['exclude_groups'])) {

            $locales = Locale::all();

            foreach ($locales as $locale) {
                Translation::firstOrCreate(array(
                    'locale_id' => $locale->id,
                    'group_id' => $group,
                    'key' => $key,
                ));
            }
        }
    }

    public function importTranslations()
    {
        $counter = 0;
        foreach($this->files->directories($this->app->langPath()) as $langPath){
            $locale = basename($langPath);

            foreach($this->files->allfiles($langPath) as $file) {

                $info = pathinfo($file);
                $group = $info['filename'];

                if(in_array($group, $this->config['exclude_groups'])) {
                    continue;
                }

                if ($langPath != $info['dirname']) {
                    $group = substr(str_replace($langPath, '', $info['dirname']).'/'.$info['filename'], 1);
                }

                $g = Group::firstOrNew(array(
                    'label' => $group
                ));
                $g->save();

                $l = Locale::firstOrNew(array(
                    'label' => $locale
                ));
                $l->save();

                $translations = \Lang::getLoader()->load($locale, $group);

                if ($translations && is_array($translations)) {

                    foreach(array_dot($translations) as $key => $value){
                        // process only string values
                        if(is_array($value)){
                            continue;
                        }

                        $value = (string) $value;

                        $translation = new Translation([
                            'locale_id' => $l->id,
                            'key' => $key
                        ]);

                        $g->translations()->save($translation);

                        // Check if the database is different then the files
                        $newStatus = $translation->value === $value ? Translation::STATUS_SAVED : Translation::STATUS_CHANGED;
                        if($newStatus !== (int) $translation->status){
                            $translation->status = $newStatus;
                        }

                        $translation->value = $value;

                        $translation->save();

                        $counter++;
                    }
                }
            }
        }
        return $counter;
    }

    public function findTranslations($path = null)
    {
        $path = $path ?: base_path();
        $keys = array();
        $functions =  array('trans', 'trans_choice', 'Lang::get', 'Lang::choice', 'Lang::trans', 'Lang::transChoice', '@lang', '@choice');
        $pattern =                              // See http://regexr.com/392hu
            "[^\w|>]".                          // Must not have an alphanum or _ or > before real method
            "(".implode('|', $functions) .")".  // Must start with one of the functions
            "\(".                               // Match opening parenthese
            "[\'\"]".                           // Match " or '
            "(".                                // Start a new group to match:
                "[a-zA-Z0-9_-]+".               // Must start with group
                "([.][^\1)]+)+".                // Be followed by one or more items/keys
            ")".                                // Close group
            "[\'\"]".                           // Closing quote
            "[\),]";                            // Close parentheses or new parameter

        // Find all PHP + Twig files in the app folder, except for storage
        $finder = new Finder();
        $finder->in($path)->exclude('storage')->name('*.php')->name('*.twig')->files();

        /** @var \Symfony\Component\Finder\SplFileInfo $file */
        foreach ($finder as $file) {
            // Search the current file for the pattern
            if(preg_match_all("/$pattern/siU", $file->getContents(), $matches)) {
                // Get all matches
                foreach ($matches[2] as $key) {
                    $keys[] = $key;
                }
            }
        }
        // Remove duplicates
        $keys = array_unique($keys);

        // Add the translations to the database, if not existing.
        foreach($keys as $key){
            // Split the group and item
            list($group, $item) = explode('.', $key, 2);
            $this->missingKey('', $group, $item);
        }

        // Return the number of found translations
        return count($keys);
    }

    public function exportTranslations($group)
    {
        if($group == '*')
                return $this->exportAllTranslations();


        $g = Group::find($group);

        if(!in_array($g->label, $this->config['exclude_groups'])) {

            $tree = $this->makeTree(Translation::where('group_id', $g->id)->whereNotNull('value')->get());

            foreach($tree as $locale => $groups){

                $filename = $g->label;

                if(isset($groups[$g->label])){

                    if(str_contains($g->label, '/')){

                        $groupPath = $g->label;

                        $filename = basename($groupPath);
                        $folder = dirname($groupPath);

                        $directoryPath = $this->app->langPath().'/'.$locale.'/'.$folder;

                        if(!$this->files->exists($directoryPath)){
                            $this->files->makeDirectory($directoryPath, 493, true, true);
                        }
                        $path = $this->app->langPath()."/$locale/$folder/$filename.php";

                    }else{
                        $path = $this->app->langPath()."/$locale/$filename.php";
                    }
                    $translations = $groups[$g->label];
                    $path = $this->app->langPath().'/'.$locale.'/'.$g->label.'.php';
                    $output = "<?php\n\nreturn ".var_export($translations, true).";\n";
                    $this->files->put($path, $output);

                }
            }
            Translation::where('group_id', $g->id)->whereNotNull('value')->update(array('status' => Translation::STATUS_SAVED));
        }
    }

    public function exportAllTranslations()
    {
        $groups = Group::all();

        foreach($groups as $g){
            $this->exportTranslations($g->id);
        }
    }

    public function cleanTranslations()
    {
        Translation::whereNull('value')->delete();
    }

    public function truncateTranslations()
    {
        Translation::truncate();
    }

    public function truncateGroups()
    {
        Group::truncate();
    }

    public function truncateLocales()
    {
        Locale::truncate();
    }

    protected function makeTree($translations)
    {
        $array = array();
        foreach($translations as $translation){

            $locale = Locale::find($translation->locale_id);

            array_set($array[$locale->label][$translation->group->label], $translation->key, $translation->value);
        }
        return $array;
    }

    public function getConfig($key = null)
    {
        if($key == null) {
            return $this->config;
        }
        else {
            return $this->config[$key];
        }
    }

}
