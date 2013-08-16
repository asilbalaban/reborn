<?php

namespace Pages\Controller\Admin;

use Pages\Model\Pages;
use Reborn\MVC\View\Theme as Theme;

class PagesController extends \AdminController
{
    public function before()
    {
        $this->menu->activeParent('content');

        $this->template->style('pages.css','pages');
        $this->template->script('pages.js','pages');

    }

    /**
     * Page Index
     *
     * @return void
     **/
    public function index()
    {
        $all = Pages::page_structure();

        $this->template->title('Manage Your Pages')
                    ->set('pages', $all)
                    ->setPartial('admin/index')
                    ->script(array(
                            'plugins/jquery.ui.touch-punch.min.js',
                            'plugins/jquery.mjs.nestedSortable.js'
                    ));
    }

    /**
     * Page Create
     *
     * @return void
     **/
    public function create()
    {
        if (!user_has_access('pages.create')) {
                return $this->notFound();
        }

        if (\Input::isPost()) {

            $validation = self::validate();

            if ($validation->valid()) {
                $page_insert = self::saveValues('create');

                if ($page_insert) {
                    \Flash::success(t('pages::pages.messages.success.add'));

                    return \Redirect::to(adminUrl('pages'));
                }
            } else {
                $errors = $validation->getErrors();
                $this->template->errors = $errors;
            }
            $page = \Input::get('*');
            $this->template->set('page',$page);
        }
        self::formElements();
        $this->template->title('Create Page')
                    ->set('method','create')
                    ->setPartial('admin/form');
    }

    /**
     * Page Edit
     *
     * @return void
     **/
    public function edit($id = null)
    {

        if (!user_has_access('pages.create')) {
                return $this->notFound();
        }

        if (\Input::isPost()) {
            $validation = self::validate();

            if ($validation->valid()) {
                //get parent id
                $page_insert = self::saveValues('edit', \Input::get('id'));

                if ($page_insert) {
                    \Flash::success(t('pages::pages.messages.success.edit'));

                    return \Redirect::to(adminUrl('pages'));
                }
            } else {
                $errors = $validation->getErrors();
                $this->template->errors = $errors;
            }
            $page = \Input::get('*');
        } else {
            $page = Pages::find($id)->toArray();
        }
        self::formElements();
        $this->template->title('Edit Page')
                    ->set('method','edit')
                    ->set('page', $page)
                    ->setPartial('admin/form');
    }

    /**
     * Page Duplicate
     *
     * @return void
     **/
    public function duplicate($id)
    {

        if (!user_has_access('pages.create')) {
                return $this->notFound();
        }

        $page = Pages::find($id)->toArray();
        self::formElements();
        $this->template->title('Add new Page')
                    ->set('method','create')
                    ->set('page', $page)
                    ->setPartial('admin/form');
    }

    /**
     * Set Value for DB save
     *
     * @return object
     **/
    protected function saveValues($method, $id = null)
    {
        if ($method == 'create') {
            $page = new Pages;
        } else {
            $page = Pages::find($id);
        }
        $parent_id = \Input::get('parent_id');
        if (!empty($parent_id)) {
            $page->parent_id = $parent_id;
            $parent_uri = Pages::get_parent_uri((int) $parent_id);
            $uri = $parent_uri.'/'.\Input::get('slug');
        } else {
            $uri = \Input::get('slug');
        }
        $current_user = \Sentry::getUser();
        $button_save = \Input::get('page_save');
        $status = ($button_save == 'Save' || $button_save == 'Publish') ? 'live' : 'draft';
        $page->title = \Input::get('title');
        $page->slug = \Input::get('slug');
        $page->uri = $uri;
        $page->content = \Input::get('content');
        $page->page_layout = \Input::get('page_layout');
        $page->meta_title = \Input::get('meta_title');
        $page->meta_keyword = \Input::get('meta_keyword');
        $page->meta_description = \Input::get('meta_description');
        $page->css = \Input::get('css');
        $page->js = \Input::get('js');
        $page->comments_enable = \Input::get('comments_enable');
        $page->status = $status;
        $page->author_id = $current_user->id; //get author_id

        $page_save = $page->save();

        if ($page_save) {
            return $page->id;
        } else {
            return $page_save;
        }
    }

    /**
     * Autosave Page
     *
     * @return json
     **/
    public function autosave()
    {
        $ajax = $this->request->isAjax();
        if ($ajax) {
            if (\Input::isPost()) {
                if ((\Input::get('title') == '') and (\Input::get('slug') == '') and (\Input::get('content') == '')) {
                    return json_encode(array('status' => 'no_save'));
                } else {
                    if (\Input::get('id') == '') {
                        $save = self::saveValues('create');
                    } else {
                        $save = self::saveValues('edit', \Input::get('id'));
                    }

                    if ($save) {
                        return json_encode(array('status' => 'save', 'post_id' => $save, 'time' => sprintf(t('pages::pages.messages.success.autosave_on'), date('d - M - Y H:i A', time()))));
                    }
                }
            }
        }
        return \Redirect::to(adminUrl('pages'));
    }

    /**
     * Delete Page
     *
     * @return void
     **/
    public function delete($id)
    {
        if (!user_has_access('pages.delete')) {
                return $this->notFound();
        }

        $page = Pages::find($id);
        $parent_id = $page->parent_id;
        $parent_uri = Pages::get_parent_uri((int) $parent_id);
        $page_delete = $page->delete();

        if ($page_delete) {
            $child_pages = Pages::where('parent_id', '=', $id)->get();
            foreach ($child_pages as $child) {
                $child_page = Pages::find((int) $child->id);
                $child_uri = $parent_uri.'/'.$child_page->slug;
                $child_page->parent_id = $parent_id;
                $child_page->uri = $parent_uri;
                $child_page->save();
            }
        } else {
            //error
        }

        return \Redirect::to(adminUrl('pages'));
    }

    /**
     * Change Page Status
     *
     * @return void
     **/
    public function status($id)
    {
        if (!user_has_access('pages.edit')) {
                return $this->notFound();
        }

        $page = Pages::find($id);

        if ($page->status == 'draft') {
            $page->status = 'live';
        } else {
            $page->status = 'draft';
        }

        $status_update = $page->save();

        if ($status_update) {
            \Flash::success(t('pages::pages.messages.success.status_update'));
        } else {
            \Flash::error(t('pages::pages.messages.error.status_update'));
        }

        return \Redirect::to('admin/pages');
    }

    /**
     * Form Validation
     *
     * @return void
     **/
    protected function validate()
    {
        $rule = array(
            'title' => 'required|maxLength:225',
            'slug' => 'required|maxLength:225'
        );

        $v = new \Reborn\Form\Validation(\Input::get('*'), $rule);

        return $v;
    }

    protected function layoutList()
    {
        $current_theme = \Setting::get('public_theme');
        $theme = new Theme($current_theme, THEMES);
        $layouts = $theme->getLayouts();
        foreach ($layouts as $key => $val) {
            $value = str_replace('.html', '', $val);
            $name = ucfirst($value);
            $list[$value] = $name;
        }

        return $list;
    }

    protected function formElements()
    {
        $layout_list = self::layoutList();
        $this->template->useWysiwyg()
                ->style(array(
                    'form.css',
                    'plugins/codemirror/codemirror.css'
                ))
                ->script(array(
                    'form.js',
                    //'plugins/jquery.colorbox.js',
                    'plugins/codemirror/codemirror.js',
                    'plugins/codemirror/css.js',
                    'plugins/codemirror/javascript.js'
                ))
                ->set('layoutList', $layout_list);
    }

    /**
     * Check slug
     *
     * @return void
     **/
    public function checkSlug()
    {
        $slug = \Input::get('slug');
        if ($slug == "") {
            return "*** This Field is required.";
        } else {
            $id = \Input::get('id');
            if ($id != '') {
                //page edit check slug
                $data = Pages::where('slug', '=', $slug)->where('id', '!=', $id)->get();
            } else {
                //page create check slug
                $data = Pages::where('slug', '=', $slug)->get();
            }
            if (count($data) > 0) {
                $error_msg = t('pages::pages.messages.error.slug_duplicate');

                return $error_msg;
            }
        }
        $this->template->partialOnly();
    }

    /**
     * Save Page Order after sorting
     *
     * @return void
     **/
    public function order()
    {
        $result = \Input::get('order');
        $order = 0;
        foreach ($result as $page_order) {
            $id = (int) $page_order['id'];
            $page = Pages::find($id);
            $page->page_order = $order;
            $page->parent_id = null;
            $page->uri = $page->slug;
            if (isset($page_order['children'])) {
                self::orderChild($page_order['children'],$id);
            }
            $order_save = $page->save();
            $order++;
        }
        $get_pages = Pages::page_structure();
        $this->template->setPartial('admin/index')
                    ->set('pages', $get_pages)
                    ->partialOnly();
    }

    /**
     * Save Page structure of children
     *
     * @return void
     * @author
     **/
    protected function orderChild ($children,$parent_id)
    {
        $order = 0;
        foreach ($children as $child) {
            $id = (int) $child['id'];
            $page = Pages::find($id);
            $parent_uri = Pages::get_parent_uri($parent_id);
            $new_uri = $parent_uri.'/'.$page->slug;
            $page->page_order = $order;
            $page->parent_id = $parent_id;
            $page->uri = $new_uri;
            $page->save();
            if (isset($child['children'])) {
                self::orderChild($child['children'],$id);
            }
            $order++;
        }
    }

    public function after($response)
    {
        return parent::after($response);
    }
}
