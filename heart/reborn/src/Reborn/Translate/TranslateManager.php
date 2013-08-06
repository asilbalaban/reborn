<?php

namespace Reborn\Translate;

/**
 * Translate Manager Class for Reborn.
 *
 * @package Reborn\Translate
 * @author Myanmar Links Professional Web Development Team
 **/
class TranslateManager
{
    /**
     * Variable for locale
     *
     * @var string
     **/
    public static $locale = null;

    /**
     * Variable for fallback locale
     *
     * @var string
     **/
    public static $fallback_locale;

    /**
     * Supported File Loader key name and class name (value)
     *
     * @var array
     **/
    protected static $fileLoaders = array(
            'file'      => 'Reborn\Translate\Loader\PHPFileLoader',
        );

    /**
     * Variable for File path (Module Path, Theme Path)
     *
     * @var array
     **/
    protected static $paths = array();

    /**
     * Language cache variable
     *
     * @var array
     **/
    protected static $caches = array();

    /**
     * Set the Language File Path.
     * Default path are [ CORE_MODULES, MODULES, THEMES, ADMIN_THEME ]
     *
     * @param array $paths Path array for language file.
     * @return void
     */
    public static function setPath($path = array())
    {
        if (empty(static::$paths)) {
            static::$paths =array(CORE_MODULES, MODULES, THEMES, ADMIN_THEME);
        }

        if (! empty($path)) {
            static::$path = array_push(static::$paths, $path);
        }
    }

    /**
     * Initialize method for Translate Manager.
     * Set locale and fallback locale for Manager.
     *
     * @param string $locale Locale code
     * @param string $fallback_locale Fallback locale, default is 'en'
     * @return viod
     */
    public static function initialize($locale, $fallback_locale = 'en')
    {
        static::setPath();
        static::$locale = $locale;
        static::$fallback_locale = $fallback_locale;
    }

    /**
     * Load the language file
     * If you will use language file, first you need to load the these file
     *
     * <code>
     *  // Load lang navigation file from naviagtion module.
     *  Trnaslate::load('navigation::navigation');
     *  // Now you can call lang string
     *  Translate::get('navigation::navigation.title');
     *
     *  // This is need very long string for lang string at somethime.
     *  // So we use subname for lang file
     *  Translate::load('navigation::navigation', 'nav')
     *  Translate::get('nav.title');
     *
     * @param string $resource Resource File name
     * @param string $subname SubName for Resource File. This is shortcut name
     * @param string $locale This is optional
     * @param string $type Loader type
     * @return boolean
     */
    public static function load($resource, $subname = null, $locale = null, $type = 'file')
    {
        $locale = is_null($locale) ? static::$locale : $locale;

        if (isset(static::$fileLoaders[$type])) {
            $loaderClass = static::$fileLoaders[$type];
        } else {
            throw new RbException("File Loader {$type} is not supported driver!");
        }

        if (class_exists($loaderClass)) {
            $class = new $loaderClass(static::$paths, $locale);
            $data = $class->load($resource);

            if ($data) {
                if (! is_null($subname) ) {
                    static::$caches[$locale][$subname] = $data;
                }
                static::$caches[$locale][$resource] = $data;
                return true;
            }

            return false;
        }

        return false;
    }

    /**
     * Get the language resource string.
     *
     * @param string $key
     * @param string $default Default result, will return not found $key
     * @param string $locale
     * @param string $type
     * @return string|null
     **/
    public static function get($key, $default = null, $locale = null, $type = 'file')
    {
        $locale = is_null($locale) ? static::$locale : $locale;
        $k = explode('.', $key);

        // If data does't exits in cache, call the load()
        if (!isset(static::$caches[$locale][$k[0]])) {
            static::load($k[0], null, $locale, $type);
        }

        if (isset(static::$fileLoaders[$type])) {
            $loaderClass = static::$fileLoaders[$type];
        } else {
            throw new RbException("File Loader {$type} is not supported driver!");
        }

        if (isset(static::$caches[$locale][$k[0]])) {
            $data = static::$caches[$locale][$k[0]];
            $class = new $loaderClass();
            return $class->get($key, $data, $default);
        }

        return $default;
    }

} // END class Translate Manager
