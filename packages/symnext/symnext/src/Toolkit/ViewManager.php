<?php

/**
 * @package Toolkit
 */

namespace Symnext\Toolkit;

use Symnext\Core\App;
use Symnext\Database\PageQuery;
use Symnext\Toolkit\Files;

/**
 * The `PageManager` class is responsible for providing basic CRUD operations
 * for Symphony frontend pages. These pages are stored in the database in
 * `tbl_pages` and are resolved to an instance of `FrontendPage` class from a URL.
 * Additionally, this manager provides functions to access the Page's types,
 * and any linked datasources or events.
 *
 * @since Symphony 2.3
 */
class ViewManager
{
    private static $pool = [];

    /*public static function initialise()
    {
        // Load page meta data.
        $views = glob(VIEWS . '/*', GLOB_ONLYDIR);
        if (!empty($views)) {
            foreach ($views as $view) {
                if (is_file)
        }
    }*/

    /**
     * Given an associative array of data, where the key is the column name
     * in `tbl_pages` and the value is the data, this function will create a
     * new Page and return a Page ID on success.
     *
     * @param array $fields
     *  Associative array of field names => values for the Page
     * @throws DatabaseException
     * @return integer|boolean
     *  Returns the Page ID of the created Page on success, false otherwise.
     */
    public static function add(array $fields) : int|bool
    {

    }

    /**
     * Given a Page ID and an array of types, this function will add Page types
     * to that Page. If a Page types are stored in `tbl_pages_types`.
     *
     * @param integer $page_id
     *  The Page ID to add the Types to
     * @param array $types
     *  An array of page types
     * @throws DatabaseException
     * @return boolean
     */
    public static function addPageTypesToPage(
        int $page_id = null,
        array $types
    ): bool
    {
        return true;
    }

    /**
     * Returns the path to the page-template by looking at the
     * `WORKSPACE/template/` directory, then at the `TEMPLATES`
     * directory for `$name.xsl`. If the template is not found,
     * false is returned
     *
     * @param string $name
     *  Name of the template
     * @return string|bool
     *  String, which is the path to the template if the template is found,
     *  false otherwise
     */
    public static function getTemplate(string $name): string|bool
    {
        $format = '%s/%s.xsl';

        if (file_exists($template = sprintf($format, WORKSPACE . '/template', $name))) {
            return $template;
        } elseif (file_exists($template = sprintf($format, TEMPLATE, $name))) {
            return $template;
        } else {
            return false;
        }
    }

    /**
     * This function creates the initial `.xsl` template for the page, whether
     * that be from the `TEMPLATES/blueprints.page.xsl` file, or from an existing
     * template with the same name. This function will handle the renaming of a page
     * by creating the new files using the old files as the templates then removing
     * the old template. If a template already exists for a Page, it will not
     * be overridden and the function will return true.
     *
     * @see toolkit.PageManager#resolvePageFileLocation()
     * @see toolkit.PageManager#createHandle()
     * @param string $new_path
     *  The path of the Page, which is the handles of the Page parents. If the
     *  page has multiple parents, they will be separated by a forward slash.
     *  eg. article/read. If a page has no parents, this parameter should be null.
     * @param string $new_handle
     *  The new Page handle, generated using `PageManager::createHandle`.
     * @param string $old_path (optional)
     *  This parameter is only required when renaming a Page. It should be the 'old
     *  path' before the Page was renamed.
     * @param string $old_handle (optional)
     *  This parameter is only required when renaming a Page. It should be the 'old
     *  handle' before the Page was renamed.
     * @throws Exception
     * @return boolean
     *  true when the page files have been created successfully, false otherwise.
     */
    public static function createPageFiles(
        string $new_path = null,
        string $new_handle = null,
        string $old_path = null,
        string $old_handle = null
    ): bool
    {
        $new = self::resolvePageFileLocation($new_path, $new_handle);
        $old = self::resolvePageFileLocation($old_path, $old_handle);

        // Nothing to do:
        if (file_exists($new) && $new == $old) {
            return true;
        }

        // Old file doesn't exist, use template:
        if (!file_exists($old)) {
            $data = file_get_contents(self::getTemplate('blueprints.page'));
        } else {
            $data = file_get_contents($old);
        }

        /**
         * Just before a Page Template is about to be created & written to disk
         *
         * @delegate PageTemplatePreCreate
         * @since Symphony 2.2.2
         * @param string $context
         * '/blueprints/pages/'
         * @param string $file
         *  The path to the Page Template file
         * @param string $contents
         *  The contents of the `$data`, passed by reference
         */
        App::ExtensionManager()->notifyMembers(
            'PageTemplatePreCreate',
            '/blueprints/pages/',
            ['file' => $new, 'contents' => &$data]
        );

        if (PageManager::writePageFiles($new, $data)) {
            // Remove the old file, in the case of a rename
            General::deleteFile($old);

            /**
             * Just after a Page Template is saved after been created.
             *
             * @delegate PageTemplatePostCreate
             * @since Symphony 2.2.2
             * @param string $context
             * '/blueprints/pages/'
             * @param string $file
             *  The path to the Page Template file
             */
            App::ExtensionManager()->notifyMembers('PageTemplatePostCreate', '/blueprints/pages/', ['file' => $new]);

            return true;
        }

        return false;
    }

    /**
     * A wrapper for `General::writeFile`, this function takes a `$path`
     * and a `$data` and writes the new template to disk.
     *
     * @param string $path
     *  The path to write the template to
     * @param string $data
     *  The contents of the template
     * @return boolean
     *  true when written successfully, false otherwise
     */
    public static function writePageFiles($path, $data)
    {
        return General::writeFile(
            $path, $data, App::Configuration()->get('write_mode', 'file')
        );
    }

    /**
     * This function will update a Page in `tbl_pages` given a `$page_id`
     * and an associative array of `$fields`. A third parameter, `$delete_types`
     * will also delete the Page's associated Page Types if passed true.
     *
     * @see toolkit.PageManager#addPageTypesToPage()
     * @param integer $page_id
     *  The ID of the Page that should be updated
     * @param array $fields
     *  Associative array of field names => values for the Page.
     *  This array does need to contain every value for the Page, it
     *  can just be the changed values.
     * @param boolean $delete_types
     *  If true, this parameter will cause the Page Types of the Page to
     *  be deleted. By default this is false.
     * @return boolean
     */
    public static function edit($page_id, array $fields, $delete_types = false)
    {
        if (!is_numeric($page_id)) {
            return false;
        }

        if (isset($fields['id'])) {
            unset($fields['id']);
        }

        // Force parent to be null if empty
        if (isset($fields['parent']) && empty($fields['parent'])) {
            $fields['parent'] = null;
        }

        if (App::Database()
                ->update('tbl_pages')
                ->set($fields)
                ->where(['id' => General::intval($page_id)])
                ->execute()
                ->success()
        ) {
            // If set, this will clear the page's types.
            if ($delete_types) {
                PageManager::deletePageTypes($page_id);
            }

            return true;
        }
        return false;
    }

    /**
     * Given a Page's `$path` and `$handle`, this function will remove
     * it's templates from the `PAGES` directory returning boolean on
     * completion
     *
     * @param string $page_path
     *  The path of the Page, which is the handles of the Page parents. If the
     *  page has multiple parents, they will be separated by a forward slash.
     *  eg. article/read. If a page has no parents, this parameter should be null.
     * @param string $handle
     *  A Page handle, generated using `PageManager::createHandle`.
     * @throws Exception
     * @return boolean
     */
    public static function deletePageFiles(
        string $page_path = null,
        string $handle
    ): bool
    {
        $file = PageManager::resolvePageFileLocation($page_path, $handle);

        // Nothing to do:
        if (!file_exists($file)) {
            return true;
        }

        // Delete it:
        if (General::deleteFile($file)) {
            return true;
        }

        return false;
    }

    /**
     * Returns the first Page that match the given `$type`.
     *
     * @since Symphony 3.0.0
     *  It returns only the first page of the specified type.
     * @param string $type
     *  Where the type is one of the available Page Types.
     * @return array|null
     *  An associative array of Page information with the key being the column
     *  name from `tbl_pages` and the value being the data. If multiple Pages
     *  are found, an array of Pages will be returned. If no Pages are found
     *  null is returned.
     */
    public static function fetchPageByType(string $type): array|null
    {
        $pageQuery = (new PageManager)
            ->select()
            ->innerJoin('tbl_pages_types')
            ->alias('pt')
            ->on(['p.id' => '$pt.page_id'])
            ->where(['pt.type' => $type])
            ->limit(1);
        return $pageQuery->execute()->next();
    }

    /**
     * This function returns a Page's Page Types. If the `$page_id`
     * parameter is given, the types returned will be for that Page.
     *
     * @param integer $page_id
     *  The ID of the Page.
     * @return array
     *  An array of the Page Types
     */
    public static function fetchPageTypes(int $page_id = null): array
    {
        $sql = App::Database()
            ->select(['pt.type'])
            ->from('tbl_pages_types')
            ->alias('pt')
            ->groupBy(['pt.type'])
            ->orderBy(['pt.type' => 'ASC']);

        if ($page_id) {
            $sql->where(['pt.page_id' => $page_id]);
        }

        return $sql->execute()->column('type');
    }

    /**
     * Returns all the page types that exist in this Symphony install.
     * There are 6 default system page types, and new types can be added
     * by Developers via the Page Editor.
     *
     * @since Symphony 2.3 introduced the JSON type.
     * @return array
     *  An array of strings of the page types used in this Symphony
     *  install. At the minimum, this will be an array with the values
     * 'index', 'XML', 'JSON', 'admin', '404' and '403'.
     */
    public static function fetchAvailablePageTypes(): array
    {
        $system_types = ['index', 'XML', 'JSON', 'admin', '404', '403'];

        $types = PageManager::fetchPageTypes();

        return !empty($types) ? General::array_remove_duplicates(array_merge($system_types, $types)) : $system_types;
    }

    /**
     * Fetch an associated array with Page ID's and the types they're using.
     *
     * @throws DatabaseException
     * @return array
     *  A 2-dimensional associated array where the key is the page ID.
     */
    public static function fetchAllPagesPageTypes(): array
    {
        return $page_types;
    }

    /**
     * Given a name, this function will return a page handle. These handles
     * will only contain latin characters
     *
     * @param string $name
     *  The Page name to generate a handle for
     * @return string
     */
    public static function createHandle(string $name): string
    {
        return Lang::createHandle($name, 255, '-', false, true, [
            '@^[^a-z\d]+@i' => '',
            '/[^\w\-\.]/i' => ''
        ]);
    }

    /**
     * This function takes a `$path` and `$handle` and generates a flattened
     * string for use as a filename for a Page's template.
     *
     * @param string $path
     *  The path of the Page, which is the handles of the Page parents. If the
     *  page has multiple parents, they will be separated by a forward slash.
     *  eg. article/read. If a page has no parents, this parameter should be null.
     * @param string $handle
     *  A Page handle, generated using `PageManager::createHandle`.
     * @return string
     */
    public static function createFilePath(
        string $path = null,
        string $handle = null
    ): string
    {
        return trim(str_replace('/', '_', $path . '_' . $handle), '_');
    }

    /**
     * This function will return the number of child pages for a given
     * `$page_id`. This is a recursive function and will return the absolute
     * count.
     *
     * @param integer $page_id
     *  The ID of the Page.
     * @return integer
     *  The number of child pages for the given `$page_id`
     */
    public static function getChildPagesCount(int $page_id = null): int|null
    {
        if (is_null($page_id)) {
            die("null page.");//return null;
        }

        $children = (new PageManager)
            ->select()
            ->where(['parent' => $page_id])
            ->execute()
            ->rows();

        $count = count($children);

        if ($count > 0) {
            foreach ($children as $c) {
                $count += self::getChildPagesCount($c['id']);
            }
        }

        return $count;
    }

    /**
     * Returns boolean if a the given `$type` has been used by Symphony
     * for a Page that is not `$page_id`.
     *
     * @param integer $page_id
     *  The ID of the Page to exclude from the query.
     * @param string $type
     *  The Page Type to look for in `tbl_page_types`.
     * @return boolean
     *  true if the type is used, false otherwise
     */
    public static function hasPageTypeBeenUsed(int $page_id = null, string $type): bool
    {
        return count(App::Database()
            ->select(['pt.id'])
            ->from('tbl_pages_types', 'pt')
            ->where(['pt.page_id' => ['!=' => $page_id]])
            ->where(['pt.type' => $type])
            ->limit(1)
            ->execute()
            ->rows()) === 1;
    }

    /**
     * Given a page ID, this function returns boolean true if the page has child pages.
     *
     * @param integer $page_id
     *  The ID of the Page to check
     * @return boolean
     *  true if the page has children, false otherwise
     */
    public static function hasChildPages(int $page_id): bool
    {
        return count(App::Database()
            ->select(['p.id'])
            ->from('tbl_pages', 'p')
            ->where(['p.parent' => $page_id])
            ->limit(1)
            ->execute()
            ->rows()) === 1;
    }

    /**
     * Resolves the path to this page's XSLT file. The Symphony convention
     * is that they are stored in the `PAGES` folder. If this page has a parent
     * it will be as if all the / in the URL have been replaced with _. ie.
     * /articles/read/ will produce a file `articles_read.xsl`
     *
     * @see toolkit.PageManager#createFilePath()
     * @param string $path
     *  The URL path to this page, excluding the current page. ie, /articles/read
     *  would make `$path` become articles/
     * @param string $handle
     *  The handle of the page.
     * @return string
     *  The path to the XSLT of the page
     */
    public static function resolvePageFileLocation(
        string $path = null, string $handle = null
    ): string
    {
        return PAGES . '/' . PageManager::createFilePath($path, $handle) . '.xsl';
    }

    /**
     * Given the `$page_id` and a `$column`, this function will return an
     * array of the given `$column` for the Page, including all parents.
     *
     * @param mixed $page_id
     *  The ID of the Page that currently being viewed, or the handle of the
     *  current Page
     * @param string $column
     *  The column to return
     * @return array
     *  An array of the current Page, containing the `$column`
     *  requested. The current page will be the last item the array, as all
     *  parent pages are prepended to the start of the array
     */
    public static function resolvePage(int|string $page_id, string $column): array
    {
        $query = (new PageManager)
            ->select(['p.parent', "p.$column"])
            ->limit(1);

        if (General::intval($page_id) > 0) {
            $query->page($page_id);
        } else {
            $query->handle($page_id);
        }

        $page = $query->execute()->next();

        if (empty($page)) {
            return $page;
        }

        $path = [$page[$column]];

        if (!empty($page['parent'])) {
            $next_parent = $page['parent'];

            while ($next_parent &&
                $parent = (new PageManager)
                    ->select(['p.parent', "p.$column"])
                    ->page($next_parent)
                    ->limit(1)
                    ->execute()
                    ->next()
            ) {
                array_unshift($path, $parent[$column]);
                $next_parent = $parent['parent'];
            }
        }

        return $path;
    }

    /**
     * Given a page ID, return the complete title of the current page.
     * Each part of the Page's title will be separated by ': '.
     *
     * @param mixed $page_id
     *  The ID of the Page that currently being viewed, or the handle of the
     *  current Page
     * @return string
     *  The title of the current Page. If the page is a child of another
     *  it will be prepended by the parent and a colon, ie. Articles: Read
     */
    public static function resolvePageTitle(int|string $page_id): string
    {
        $path = PageManager::resolvePage($page_id, 'title');

        return implode(': ', $path);
    }

    /**
     * Given the `$page_id`, return the complete path to the
     * current page. Each part of the Page's path will be
     * separated by '/'.
     *
     * @param mixed $page_id
     *  The ID of the Page that currently being viewed, or the handle of the
     *  current Page
     * @return string
     *  The complete path to the current Page including any parent
     *  Pages, ie. /articles/read
     */
    public static function resolvePagePath(int|string $page_id): string
    {
        $path = PageManager::resolvePage($page_id, 'handle');

        return implode('/', $path);
    }

    /**
     * Resolve a page by it's handle and path
     *
     * @param string $handle
     *  The handle of the page
     * @param boolean $path
     *  The path to the page
     * @return array
     *  array if found, null if not
     */
    public static function resolvePageByPath(string $handle, bool $path = false): ?array
    {
        return (new PageManager)
            ->select()
            ->handle($handle)
            ->path(!$path ? null : $path)
            ->limit(1)
            ->execute()
            ->next();
    }

    /**
     * Check whether a data source is used or not
     *
     * @param string $handle
     *  The data source handle
     * @return boolean
     *  true if used, false if not
     */
    public static function isDataSourceUsed(string $handle): bool
    {
        return (new PageManager)
            ->select()
            ->count()
            ->where(['p.data_sources' => ['regexp' => "[[:<:]]{$handle}[[:>:]]"]])
            ->execute()
            ->integer(0) > 0;
    }

    /**
     * Check whether a event is used or not
     *
     * @param string $handle
     *  The event handle
     * @return boolean
     *  true if used, false if not
     */
    public static function isEventUsed(string $handle): bool
    {
        return (new PageManager)
            ->select()
            ->count()
            ->where(['p.events' => ['regexp' => "[[:<:]]{$handle}[[:>:]]"]])
            ->execute()
            ->integer(0) > 0;
    }

    /**
     * Factory method that creates a new PageQuery.
     *
     * @since Symphony 3.0.0
     * @param array $projection
     *  The projection to select.
     *  If no projection gets added, it defaults to `PageQuery::getDefaultProjection()`.
     * @return PageQuery
     */
    public function select(array $projection = []): PageQuery
    {
        return new PageQuery(App::Database(), $projection);
    }
}
