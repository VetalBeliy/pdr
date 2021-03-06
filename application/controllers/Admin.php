<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Admin extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('admin_model');
        $this->load->library('twig');
        //$this->output->cache(5);
    }

    private function _admin_access()
    {
        if ($this->session->has_userdata('login') != NULL && 
            $this->session->userdata('user_rights') == $this->config->item('admin_rights') &&
            $this->session->userdata('user_enabled') == $this->config->item('STATUS_ON')) 
        {
            return TRUE;
        }

        return FALSE;
    }

    public function index($data = '')
    {
        $data['title'] = 'Административная панель';
        
        if ($this->_admin_access() === true)
        {
            $data['page_name'] = 'Основная панель';
            $data['user_name'] = $_SESSION['login'];
            $data['comments'] = $this->admin_model->get_comments('4');
            $data['requests'] = $this->admin_model->get_requests('4');
            //$data['orders'] = $this->admin_model->get_orders('4');

            $data['method'] = 'index';
            $data['table1'] = 'order_for_assessment';
            $data['table2'] = 'comments';

            echo $this->twig->render('admin/main', $data);
        }
        
        elseif ($this->session->has_userdata('login') != NULL && ($this->session->userdata('user_rights') !== $this->config->item('admin_rights') OR  $this->session->userdata('user_enabled') == $this->config->item('STATUS_OFF')))
        {
            $this->session->sess_destroy();
            $data['error'] = 'У Вас не достаточно прав для доступа к этой странице или пользователь с таким логином выключен';
            $data['user_name'] = $_SESSION['login'];

            // send error to the view
            echo $this->twig->render('user/login/login_admin', $data);  
        }

        else
        {
            $this->login();
        }
    }

    /**
     * login function.
     * 
     * @access public
     * @return void
     */
    public function login() 
    {
        $data['title'] = 'Garage - Авторизация';

        if ($this->_admin_access() === true)
        {
            $this->index();
        }
        else
        {
            $this->load->model('user_model');
            
            // load form helper and validation library
            $this->load->helper('form');
            $this->load->library('form_validation');
            
            // set validation rules
            $this->form_validation->set_rules('login', 'Login', 'required|alpha_numeric');
            $this->form_validation->set_rules('password', 'Password', 'required');
            
            if ($this->form_validation->run() == false) 
            {
                // validation not ok, send validation errors to the view
                echo $this->twig->render('user/login/login_admin', $data);    
            } 
            
            else 
            {
                // set variables from the form
                $login = $this->input->post('login');
                $password = $this->input->post('password');
                
                if ($this->user_model->resolve_user_login($login, $password)) 
                {
                    $user_id = $this->user_model->get_user_id_from_username($login);
                    $user    = $this->user_model->get_user($user_id);
                    $user_data = $this->user_model->get_user_data($user_id);
                
                    //set session user data
                    $session_data = array(
                                    'id' => session_id(),
                                    'user_id' => (int)$user->id,
                                    'login' => (string)$user->login,
                                    'logged_in' => (bool)true,
                                    'user_enabled' => (int)$user_data->user_enabled,
                                    'user_rights' => (int)$user_data->user_rights, );

                    $this->session->set_userdata($session_data);
                    
                    if ($this->session->userdata('user_rights') == $this->config->item('admin_rights'))
                    {
                        $this->user_model->set_user_session();
            
                        // user login ok
                        $data['user_login'] = $this->session->userdata('login');
                        echo $this->twig->render('user/login/login_admin_success', $data); 
                    }

                    else
                    {
                        // login failed
                        $data['error'] = 'У Вас не достаточно прав для доступа к этой странице';

                        // send error to the view
                        echo $this->twig->render('user/login/login_admin', $data); 
                    }
                } 

                else 
                {
                    // login failed
                    $data['error'] = 'Неверный логин или пароль. Повторите ввод.';
                    
                    // send error to the view
                    echo $this->twig->render('user/login/login_admin', $data);   
                }
            }
        }   
    }

    /**
     * logout function for admin panel.
     * 
     * @access public
     * @return void
     */
    public function logout() 
    {   
        $data['title'] = 'Garage - Авторизация';
             
        if ($this->session->has_userdata('logged_in') != NULL && $this->session->userdata('logged_in') === true) 
        {
            $data['user_login'] = $this->session->userdata('login');
            $this->session->sess_destroy();

            // user logout ok
            echo $this->twig->render('user/logout/logout_admin_success', $data);    
        } 

        else 
        {
            // there user was not logged in, we cannot logged him out,
            // redirect him to site root
            redirect('/');
        }   
    }

    //metods for work with data in admin panel
    public function show_articles($data = '', $id = '')
    {
        if ($this->_admin_access() === true)
        {
            $data['articles'] = $this->admin_model->get_article($id);
            $data['user_name'] = $_SESSION['login'];
            $data['page_name'] = 'Управление контентом';

            $data['method'] = 'show_articles';
            $data['table'] = 'Content';

            echo $this->twig->render('admin/all_articles_view', $data);
        }
        else
        {
            $this->login();
        }
    }

    public function show_users($data = '')
    {
        if ($this->_admin_access() === true)
        {
            $this->load->model('user_model');
            $data['users'] = $this->user_model->get_user_info();
            $data['user_name'] = $_SESSION['login'];
            $data['page_name'] = 'Просмотр пользователей';

            $data['method'] = 'show_users';
            $data['table'] = 'users';

            echo $this->twig->render('admin/all_users_view', $data); 
        }
        else
        {
            $this->login();
        }
    }

    public function show_comments($limit = '0')
    {
        if ($this->_admin_access() === true)
        {
            $data['comments'] = $this->admin_model->get_comments($limit);
            $data['user_name'] = $_SESSION['login'];
            $data['page_name'] = 'Просмотр комментариев';

            $data['method'] = 'show_comments';
            $data['table'] = 'comments';

            echo $this->twig->render('admin/all_comments_view', $data); 
        }
        else
        {
            $this->login();
        }
    }

    public function show_requests($limit = '0')
    {
        if ($this->_admin_access() === true)
        {
            $data['requests'] = $this->admin_model->get_requests($limit);
            $data['user_name'] = $_SESSION['login'];
            $data['page_name'] = 'Просмотр запросов на оценку работ';
            $data['method'] = 'show_requests';
            $data['table'] = 'order_for_assessment';
            echo $this->twig->render('admin/pdr_requests_view', $data);
        }
        else
        {
            $this->login();
        }
    }

    public function show_cars()
    {
        if ($this->_admin_access() === true)
        {
            $data['models'] = $this->admin_model->get_cars();
            $data['marks'] = $this->admin_model->get_marks();
            $data['user_name'] = $_SESSION['login'];
            $data['page_name'] = 'Каталог автомобилей';
            echo $this->twig->render('admin/all_avto_view', $data); 
        }
        else
        {
            $this->login();
        }
    }

    public function add_car()
    {
        if ($this->_admin_access() === true)
        {
            if ($this->input->post('model') == (null OR ' ') OR $this->input->post('mark') == (null OR ' ')) 
            {
                $data['error'] = 'Заполните все поля формы';
            }
            else
            {
                $newCar = $this->input->post();
                if ($this->admin_model->add_car($newCar) === false) 
                {
                    $data['error'] = 'Ошибка при добавлении авто в базу. Вероятнее всего у этого производителя уже есть авто с таким названием в базе данных';
                }

                $data['info'] = 'Автомобиль успешно добавлен в базу';
            }
            
            $data['models'] = $this->admin_model->get_cars();
            $data['marks'] = $this->admin_model->get_marks();
            $data['user_name'] = $_SESSION['login'];
            $data['page_name'] = 'Каталог автомобилей';
            echo $this->twig->render('admin/all_avto_view', $data); 
        }
        else
        {
            $this->login();
        }
    }

    public function delete_car()
    {
        if ($this->_admin_access() === true)
        {
            if ($this->input->post('ModelName') == null) 
            {
                $data['error'] = 'Выберите авто для удаления';
            }

            else
            {
                $model_id = $this->input->post('ModelName');
                if ($this->admin_model->delete_data($model_id, 'cars_model') === false) 
                {
                    $data['error'] = 'Ошибка при удалении авто из базы';
                }

                $data['info'] = 'Автомобиль успешно удален из базы';
            }

            $data['models'] = $this->admin_model->get_cars();
            $data['marks'] = $this->admin_model->get_marks();
            $data['user_name'] = $_SESSION['login'];
            $data['page_name'] = 'Каталог автомобилей';
            echo $this->twig->render('admin/all_avto_view', $data);
        }
        else
        {
            $this->login();
        }
    }

    public function add_article() 
    {
        if ($this->_admin_access() === true)
        {
            $data = array();

            $this->load->library('form_validation');
        
            $this->form_validation->set_rules('title', 'Заголовок', 'trim|required');
            $this->form_validation->set_rules('text', 'Содержимое', 'trim|required');
            $this->form_validation->set_rules('meta', 'Теги', 'trim|required');
            $this->form_validation->set_rules('address', 'Адрес', 'trim|required');
        
            if ($this->form_validation->run() === false) 
            {
                $data['user_name'] = $_SESSION['login'];
                $data['page_name'] = 'Управление контентом';
                echo $this->twig->render('admin/add_article_view', $data);  
            }
            
            else
            {
                $this->admin_model->create_content();
                $data['info'] = 'Статья успешно добавлена';
                $this->show_articles($data);
            }
        }
        
        else
        {
            $this->login();
        }
    }

        public function edit_article($id = '') 
    {
        if ($this->_admin_access() === true)

        {
            $data = array();
            $data['get_article'] = $this->admin_model->get_article($id);
                    
            if ($this->form_validation->run() === false) 
            {
                $data['user_name'] = $_SESSION['login'];
                $data['page_name'] = 'Редактирование статьи';
                echo $this->twig->render('admin/add_article_view', $data);   
            }
            
            else
            {
                $this->admin_model->edit_content();
                $this->show_articles();
            }
        }
        
        else
        {
            $this->login();
        }
    }

    public function show_example($data = '', $id = '')
    {
        if ($this->_admin_access() === true)
        {
            $data['examples'] = $this->admin_model->get_example($id);
            $data['user_name'] = $_SESSION['login'];
            $data['page_name'] = 'Управление примерами работ';
            $data['categories'] = $this->config->item("categories");
            $data['method'] = 'show_example';
            $data['table'] = 'example_works';

            echo $this->twig->render('admin/all_examples_view', $data);
        }
        else
        {
            $this->login();
        }
    }

    public function add_example() 
    {
        if ($this->_admin_access() === true)
        {
            $data = array();

            $this->load->helper('form');
            $this->load->library('form_validation');
            $data['user_name'] = $_SESSION['login'];
        
            $this->form_validation->set_rules('category', 'category', 'required');
            $this->form_validation->set_rules('text', 'text', 'trim|required');
            $this->form_validation->set_rules('foto_before', 'foto_before', 'required');
            $this->form_validation->set_rules('foto_after', 'foto_after', 'required');
            $this->form_validation->set_rules('additionally', 'additionally', 'trim|required');            
        
            if ($this->form_validation->run() === false) 
            {
                
                $data['page_name'] = 'Управление примерами работ';
                $data['categories'] = $this->config->item("categories"); 
                echo $this->twig->render('admin/add_example_view', $data); 
            }
            
            else
            {
                $this->admin_model->create_example();
                $data['page_name'] = 'Просмотр примеров работ';
                $this->show_example(); 
            }
        }
        
        else
        {
            $this->login();
        }
    }

    public function add_user()
    {
        if ($this->_admin_access() === true)
        {
            $this->load->helper('form');
            $this->load->library('form_validation');

            $data['user_name'] = $_SESSION['login'];
            $data['page_name'] = 'Управление пользователями';

            $data['models'] = $this->admin_model->get_cars();
            $data['marks'] = $this->admin_model->get_marks();
            $data['user_right'] = array(
                                    'Админ' => $this->config->item('admin_rights'),
                                    'Пользователь' => $this->config->item('user_rights'),);

            // set validation rules
            $this->form_validation->set_rules('login', 'Логин', 'trim|required|alpha_numeric|min_length[3]|is_unique[users.login]', array('is_unique' => 'Этот логин уже занят. Пожалуйста введите другой.'));
            $this->form_validation->set_rules('name', 'Имя', 'trim|required|alpha_numeric|min_length[2]');
            $this->form_validation->set_rules('surname', 'Фамилия', 'trim|required|alpha_numeric|min_length[2]');
            $this->form_validation->set_rules('sex', 'Пол', 'required');
            $this->form_validation->set_rules('birthsday', 'Дата рождения', 'trim|required');
            $this->form_validation->set_rules('email', 'e-mail', 'trim|required|valid_email|is_unique[users.email]', array('is_unique' => 'Данный e-mail уже используется. Пожалуйста введите другой.'));
            $this->form_validation->set_rules('tel', 'Телефон', 'trim|required|alpha_numeric|min_length[10]');
            $this->form_validation->set_rules('password', 'Пароль', 'trim|required|min_length[6]');
            $this->form_validation->set_rules('password_confirm', 'Подтверждение пароля', 'trim|required|min_length[6]|matches[password]');

            if ($this->form_validation->run() === false) 
            {
                // validation not ok, send validation errors to the view
                if (validation_errors() == true) 
                {
                    $data['error'] = 'Проверьте правильность заполнения всех полей.';
                }
                
                echo $this->twig->render('admin/add_user_view', $data);  
            }

            elseif (($this->input->post('ManufactureName') !== null AND $this->input->post('ModelName') == null) OR ($this->input->post('ManufactureName') == null AND $this->input->post('car_year') !== null)) 
            {
                $data['error'] = 'Проверьте правильность заполнения полей выбора авто.'; 
                echo $this->twig->render('admin/add_user_view', $data);
            }

            else 
            {   
                $this->load->model('user_model');
                $register = $this->input->post();

                if ($this->input->post('status') == 'on')
                {
                    $register['status'] = $this->config->item('STATUS_ON');
                }
                else
                {
                    $register['status'] = $this->config->item('STATUS_OFF');
                }

                if ($this->user_model->create_user($register)) 
                {
                    // user creation ok
                    $data['info'] = 'Пользователь успешно добавлен.';
                
                    $this->show_users($data);
                } 
                
                else 
                {
                    // user creation failed, this should never happen
                    $data['error'] = 'Что-то пошло не так. Please try again.';
                    
                    // send error to the view
                    echo $this->twig->render('admin/add_user_view', $data);
                }  
            }
        }
        else
        {
            $this->login();
        }
    }

    public function show_user($id)
    {
        if ($this->_admin_access() === true)
        {
            $this->load->model('user_model');

            $data['page_name'] = 'Профиль пользователя';
            $data['user_name'] = $_SESSION['login'];
            // $data['comments'] = $this->admin_model->get_comments('4');
            // $data['requests'] = $this->admin_model->get_requests('4');
            //$data['orders'] = $this->admin_model->get_orders('4');

            $data['user'] = $this->user_model->get_user_info($id);
            $data['method'] = 'index';
            $data['table1'] = 'order_for_assessment';
            $data['table2'] = 'comments';

            echo $this->twig->render('admin/user_view', $data);
        }
        else
        {
            $this->login();
        }
    }

    public function change_user_status($id, $status, $data = '')
    {
        if ($this->_admin_access() === true)
        {
            $this->load->model('user_model');

            $data['user_enabled'] = (int)!$status;

            if ($this->user_model->change_user_status($id, $data)) 
            {
                // user creation ok
                $data['info'] = 'Пользователю успешно изменен статус.';
                $this->show_users($data);
            } 
            
            else 
            {
                // user creation failed, this should never happen
                $data['error'] = 'Что-то пошло не так. Please try again.';
                
                // send error to the view
                echo $this->twig->render('admin/all_user_view', $data);
            }  
        }
        else
        {
            $this->login();
        }
    }

    public function delete_item($method, $table, $id)
    {
        if ($this->_admin_access() === true)
        {
            if ($this->admin_model->delete_data($id, $table)) 
            {
                // item delete ok
                $data['info'] = 'Запись успешно удалена';
                $this->{$method}($data);    
            } 
            
            else 
            {
                // item delete failed, this should never happen
                $data['error'] = 'Что-то пошло не так. Please try again.';
                $this->{$method}($data); 
            }      
        }
        else
        {
            $this->login();
        }
    }
}