<?php
class ControllerMarketplaceOpenbay extends Controller {
	private $error = array();

	public function install() {
		$this->load->language('marketplace/openbay');

		$this->load->model('setting/extension');

		if (!$this->user->hasPermission('modify', 'marketplace/openbay')) {
			$this->session->data['error'] = $this->language->get('error_permission');

			$this->response->redirect($this->url->link('marketplace/openbay', 'user_token=' . $this->session->data['user_token'], true));
		} else {
			$this->model_setting_extension->install('openbay', $this->request->get['extension']);

			$this->session->data['success'] = $this->language->get('text_install_success');

			$this->load->model('user/user_group');

			$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/openbay/' . $this->request->get['extension']);
			$this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/openbay/' . $this->request->get['extension']);

			require_once(DIR_APPLICATION . 'controller/extension/openbay/' . $this->request->get['extension'] . '.php');

			$class = 'ControllerExtensionOpenbay' . str_replace('_', '', $this->request->get['extension']);
			$class = new $class($this->registry);

			if (method_exists($class, 'install')) {
				$class->install();
			}

			$this->response->redirect($this->url->link('marketplace/openbay', 'user_token=' . $this->session->data['user_token'], true));
		}
	}

	public function uninstall() {
		$this->load->language('marketplace/openbay');

		$this->load->model('setting/extension');

		if (!$this->user->hasPermission('modify', 'marketplace/openbay')) {
			$this->session->data['error'] = $this->language->get('error_permission');

			$this->response->redirect($this->url->link('marketplace/openbay', 'user_token=' . $this->session->data['user_token'], true));
		} else {
			$this->session->data['success'] = $this->language->get('text_uninstall_success');

			require_once(DIR_APPLICATION . 'controller/extension/openbay/' . $this->request->get['extension'] . '.php');

			$this->load->model('setting/extension');
			$this->load->model('setting/setting');

			$this->model_setting_extension->uninstall('openbay', $this->request->get['extension']);

			$this->model_setting_setting->deleteSetting($this->request->get['extension']);

			$class = 'ControllerExtensionOpenbay' . str_replace('_', '', $this->request->get['extension']);
			$class = new $class($this->registry);

			if (method_exists($class, 'uninstall')) {
				$class->uninstall();
			}

			$this->response->redirect($this->url->link('marketplace/openbay', 'user_token=' . $this->session->data['user_token'], true));
		}
	}

	public function index() {
		$this->load->language('marketplace/openbay');

        $data = $this->language->all();

        $this->load->model('extension/openbay/openbay');
        $this->load->model('setting/extension');
        $this->load->model('setting/setting');
        $this->load->model('extension/openbay/version');

		$this->document->setTitle($this->language->get('heading_title'));
		$this->document->addScript('view/javascript/openbay/js/faq.js');

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('marketplace/openbay', 'user_token=' . $this->session->data['user_token'], true),
		);

		$data['manage_link'] = $this->url->link('marketplace/openbay/manage', 'user_token=' . $this->session->data['user_token'], true);
		$data['product_link'] = $this->url->link('marketplace/openbay/items', 'user_token=' . $this->session->data['user_token'], true);
		$data['order_link'] = $this->url->link('marketplace/openbay/orderlist', 'user_token=' . $this->session->data['user_token'], true);

		$data['success'] = '';
		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];
			unset($this->session->data['success']);
		}

		$data['error'] = $this->model_extension_openbay_openbay->requirementTest();

		if (isset($this->session->data['error'])) {
			$data['error'][] = $this->session->data['error'];
			unset($this->session->data['error']);
		}

		$extensions = $this->model_setting_extension->getInstalled('openbay');

		foreach ($extensions as $key => $value) {
			if (!file_exists(DIR_APPLICATION . 'controller/extension/openbay/' . $value . '.php')) {
				$this->model_setting_extension->uninstall('openbay', $value);
				unset($extensions[$key]);
			}
		}

		$data['extensions'] = array();

		$markets = array('ebay', 'etsy', 'amazon', 'amazonus', 'fba');

		foreach ($markets as $market) {
			$extension = basename($market, '.php');

			$this->load->language('extension/openbay/' . $extension, 'extension');

			$data['extensions'][] = array(
				'name'      => $this->language->get('extension')->get('heading_title'),
				'edit'      => $this->url->link('extension/openbay/' . $extension . '', 'user_token=' . $this->session->data['user_token'], true),
				'status'    => ($this->config->get('openbay_' . $extension . '_status') || $this->config->get($extension . '_status')) ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
				'install'   => $this->url->link('marketplace/openbay/install', 'user_token=' . $this->session->data['user_token'] . '&extension=' . $extension, true),
				'uninstall' => $this->url->link('marketplace/openbay/uninstall', 'user_token=' . $this->session->data['user_token'] . '&extension=' . $extension, true),
				'installed' => in_array($extension, $extensions),
				'code'      => $extension
			);
		}

		$settings = $this->model_setting_setting->getSetting('feed_openbaypro');

		if (isset($settings['feed_openbaypro_version'])) {
			$data['feed_openbaypro_version'] = $settings['feed_openbaypro_version'];
		} else {
			$data['feed_openbaypro_version'] = $this->model_extension_openbay_version->version();
			$settings['feed_openbaypro_version'] = $this->model_extension_openbay_version->version();
			$this->model_setting_setting->editSetting('feed_openbaypro', $settings);
		}

		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('marketplace/openbay', $data));
	}

	public function manage() {
		$this->load->language('marketplace/openbay');

        $data = $this->language->all();

        $this->load->model('setting/setting');

		$this->document->setTitle($this->language->get('text_manage'));
		$this->document->addScript('view/javascript/openbay/js/faq.js');

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
			'text' => $this->language->get('text_home'),
		);

		$data['breadcrumbs'][] = array(
			'href' => $this->url->link('marketplace/openbay', 'user_token=' . $this->session->data['user_token'], true),
			'text' => $this->language->get('heading_title'),
		);

		$data['breadcrumbs'][] = array(
			'href' => $this->url->link('marketplace/openbay/manage', 'user_token=' . $this->session->data['user_token'], true),
			'text' => $this->language->get('text_manage'),
		);

		if ($this->request->server['REQUEST_METHOD'] == 'POST') {
			$this->model_setting_setting->editSetting('feed_openbaypro', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/openbay', 'user_token=' . $this->session->data['user_token'], true));
		}

		if (isset($this->request->post['feed_openbaypro_version'])) {
			$data['feed_openbaypro_version'] = $this->request->post['feed_openbaypro_version'];
		} else {
			$settings = $this->model_setting_setting->getSetting('feed_openbaypro');

			if (isset($settings['feed_openbaypro_version'])) {
				$data['feed_openbaypro_version'] = $settings['feed_openbaypro_version'];
			} else {
				$this->load->model('extension/openbay/version');
				$settings['feed_openbaypro_version'] = $this->model_extension_openbay_version->version();
				$data['feed_openbaypro_version'] = $this->model_extension_openbay_version->version();
				$this->model_setting_setting->editSetting('feed_openbaypro', $settings);
			}
		}

		if (isset($this->request->post['feed_openbaypro_language'])) {
			$data['feed_openbaypro_language'] = $this->request->post['feed_openbaypro_language'];
		} else {
			$data['feed_openbaypro_language'] = $this->config->get('feed_openbaypro_language');
		}

		$data['api_languages'] = array(
			'en_GB' => 'English',
			'de_DE' => 'German',
			'es_ES' => 'Spanish',
			'fr_FR' => 'French',
			'it_IT' => 'Italian',
			'nl_NL' => 'Dutch',
			'zh_HK' => 'Simplified Chinese'
		);

		$data['text_version'] = $this->config->get('feed_openbaypro_version');

		$data['action'] = $this->url->link('marketplace/openbay/manage', 'user_token=' . $this->session->data['user_token'], true);
		$data['cancel'] = $this->url->link('marketplace/openbay', 'user_token=' . $this->session->data['user_token'], true);

		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/openbay/openbay_manage', $data));
	}

	public function update() {
		$this->load->model('extension/openbay/openbay');
		$this->load->language('marketplace/openbay');

		if (!isset($this->request->get['stage'])) {
			$stage = 'check_server';
		} else {
			$stage = $this->request->get['stage'];
		}

		if (!isset($this->request->get['beta']) || $this->request->get['beta'] == 0) {
			$beta = 0;
		} else {
			$beta = 1;
		}

		switch ($stage) {
			case 'check_server': // step 1
				$response = $this->model_extension_openbay_openbay->updateTest();

				sleep(1);
				$this->response->addHeader('Content-Type: application/json');
				$this->response->setOutput(json_encode($response));
				break;
			case 'check_version': // step 2
				$response = $this->model_extension_openbay_openbay->updateCheckVersion($beta);

				sleep(1);
				$this->response->addHeader('Content-Type: application/json');
				$this->response->setOutput(json_encode($response));

				break;
			case 'download': // step 3
				$response = $this->model_extension_openbay_openbay->updateDownload($beta);

				sleep(1);
				$this->response->addHeader('Content-Type: application/json');
				$this->response->setOutput(json_encode($response));
				break;
			case 'extract': // step 4
				$response = $this->model_extension_openbay_openbay->updateExtract();

				sleep(1);
				$this->response->addHeader('Content-Type: application/json');
				$this->response->setOutput(json_encode($response));
				break;
			case 'remove': // step 5 - remove any files no longer needed
				$response = $this->model_extension_openbay_openbay->updateRemove($beta);

				$this->response->addHeader('Content-Type: application/json');
				$this->response->setOutput(json_encode($response));
				break;
			case 'run_patch': // step 6 - run any db updates or other patch files
				$this->model_extension_openbay_openbay->patch();

				$this->load->model('extension/openbay/ebay');
				$this->model_extension_openbay_ebay->patch();

				$this->load->model('extension/openbay/amazon');
				$this->model_extension_openbay_amazon->patch();

				$this->load->model('extension/openbay/amazonus');
				$this->model_extension_openbay_amazonus->patch();

				$this->load->model('extension/openbay/etsy');
				$this->model_extension_openbay_etsy->patch();

				$response = array('error' => 0, 'response' => '', 'percent_complete' => 90, 'status_message' => 'Running patch files');

				$this->response->addHeader('Content-Type: application/json');
				$this->response->setOutput(json_encode($response));
				break;
			case 'update_version': // step 7 - update the version number
				$this->load->model('setting/setting');

				$response = $this->model_extension_openbay_openbay->updateUpdateVersion($beta);

				$this->response->addHeader('Content-Type: application/json');
				$this->response->setOutput(json_encode($response));
				break;
			default;
		}
	}

	public function patch() {
		$this->load->model('extension/openbay/openbay');
		$this->load->model('extension/openbay/ebay');
		$this->load->model('extension/openbay/amazon');
		$this->load->model('extension/openbay/amazonus');
		$this->load->model('extension/openbay/etsy');
		$this->load->model('setting/extension');
		$this->load->model('setting/setting');
		$this->load->model('user/user_group');
		$this->load->model('extension/openbay/version');

		$this->model_extension_openbay_openbay->patch();
		$this->model_extension_openbay_ebay->patch();
		$this->model_extension_openbay_amazon->patch();
		$this->model_extension_openbay_amazonus->patch();
		$this->model_extension_openbay_etsy->patch();

		$openbay = $this->model_setting_setting->getSetting('feed_openbaypro');
		$openbay['feed_openbaypro_version'] = (int)$this->model_extension_openbay_version->version();
		$this->model_setting_setting->editSetting('feed_openbaypro', $openbay);

		$installed_modules = $this->model_setting_extension->getInstalled('feed');

		if (!in_array('openbay', $installed_modules)) {
			$this->model_setting_extension->install('feed', 'openbaypro');
			$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'marketplace/openbay');
			$this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'marketplace/openbay');
		}

		sleep(1);

		$json = array('msg' => 'ok');

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function notifications() {
		$this->load->model('extension/openbay/openbay');

		$json = $this->model_extension_openbay_openbay->getNotifications();

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function version() {
		$this->load->model('extension/openbay/openbay');

		$json = $this->model_extension_openbay_openbay->version();

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function faq() {
		$this->load->language('marketplace/openbay');

        $this->load->model('extension/openbay/openbay');

		$data = $this->model_extension_openbay_openbay->faqGet($this->request->get['qry_route']);

		$data['button_faq'] = $this->language->get('button_faq');
		$data['button_close'] = $this->language->get('button_close');

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($data));
	}

	public function faqDismiss() {
		$this->load->model('extension/openbay/openbay');

		$this->model_extension_openbay_openbay->faqDismiss($this->request->get['qry_route']);

		$json = array();

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function faqClear() {
		$this->load->model('extension/openbay/openbay');
		$this->model_extension_openbay_openbay->faqClear();

		$json = array('msg' => 'ok');

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function getOrderInfo() {
		$this->load->language('marketplace/openbay');

        $data = $this->language->all();

		if ($this->config->get('ebay_status') == 1) {
			if ($this->openbay->ebay->getOrder($this->request->get['order_id']) !== false) {
				if ($this->config->get('ebay_status_shipped_id') == $this->request->get['status_id']) {
					$data['carriers'] = $this->openbay->ebay->getCarriers();
					$data['order_info'] = $this->openbay->ebay->getOrder($this->request->get['order_id']);
					$this->response->setOutput($this->load->view('extension/openbay/ebay_ajax_shippinginfo', $data));
				}
			}
		}

		if ($this->config->get('openbay_amazon_status') == 1) {
			$data['order_info'] = $this->openbay->amazon->getOrder($this->request->get['order_id']);

			if ($data['order_info']) {
				if ($this->request->get['status_id'] == $this->config->get('openbay_amazon_order_status_shipped')) {
					$data['couriers'] = $this->openbay->amazon->getCarriers();
					$data['courier_default'] = $this->config->get('openbay_amazon_default_carrier');
					$this->response->setOutput($this->load->view('extension/openbay/amazon_ajax_shippinginfo', $data));
				}
			}
		}

		if ($this->config->get('openbay_amazonus_status') == 1) {
			$data['order_info'] = $this->openbay->amazonus->getOrder($this->request->get['order_id']);

			if ($data['order_info']) {
				if ($this->request->get['status_id'] == $this->config->get('openbay_amazonus_order_status_shipped')) {
					$data['couriers'] = $this->openbay->amazonus->getCarriers();
					$data['courier_default'] = $this->config->get('openbay_amazonus_default_carrier');
					$this->response->setOutput($this->load->view('extension/openbay/amazonus_ajax_shippinginfo', $data));
				}
			}
		}

		if ($this->config->get('etsy_status') == 1) {
			$data['order_info'] = $this->openbay->etsy->orderFind($this->request->get['order_id']);

			if ($data['order_info']) {
				if ($this->request->get['status_id'] == $this->config->get('etsy_order_status_shipped')) {

				}
			}
		}
	}

    public function addOrderInfo() {
        if ($this->config->get('ebay_status') == 1 && $this->openbay->ebay->getOrder($this->request->get['order_id']) !== false) {
            if ($this->config->get('ebay_status_shipped_id') == $this->request->get['status_id']) {
                $this->openbay->ebay->orderStatusListen($this->request->get['order_id'], $this->request->get['status_id'], array('tracking_no' => $this->request->post['tracking_no'], 'carrier_id' => $this->request->post['carrier_id']));
            } else {
                $this->openbay->ebay->orderStatusListen($this->request->get['order_id'], $this->request->get['status_id']);
            }
        }

        if ($this->config->get('openbay_amazon_status') == 1 && $this->openbay->amazon->getOrder($this->request->get['order_id']) !== false) {
            if ($this->config->get('openbay_amazon_order_status_shipped') == $this->request->get['status_id']) {
                if (!empty($this->request->post['courier_other'])) {
                    $this->openbay->amazon->updateOrder($this->request->get['order_id'], 'shipped', $this->request->post['courier_other'], false, $this->request->post['tracking_no']);
                } else {
                    $this->openbay->amazon->updateOrder($this->request->get['order_id'], 'shipped', $this->request->post['courier_id'], true, $this->request->post['tracking_no']);
                }
            }

            if ($this->config->get('openbay_amazon_order_status_canceled') == $this->request->get['status_id']) {
                $this->openbay->amazon->updateOrder($this->request->get['order_id'], 'canceled');
            }
        }

        if ($this->config->get('openbay_amazonus_status') == 1 && $this->openbay->amazonus->getOrder($this->request->get['order_id']) !== false) {
            if ($this->config->get('openbay_amazonus_order_status_shipped') == $this->request->get['status_id']) {
                if (!empty($this->request->post['courier_other'])) {
                    $this->openbay->amazonus->updateOrder($this->request->get['order_id'], 'shipped', $this->request->post['courier_other'], false, $this->request->post['tracking_no']);
                } else {
                    $this->openbay->amazonus->updateOrder($this->request->get['order_id'], 'shipped', $this->request->post['courier_id'], true, $this->request->post['tracking_no']);
                }
            }
            if ($this->config->get('openbay_amazonus_order_status_canceled') == $this->request->get['status_id']) {
                $this->openbay->amazonus->updateOrder($this->request->get['order_id'], 'canceled');
            }
        }

        if ($this->config->get('etsy_status') == 1) {
            $linked_order = $this->openbay->etsy->orderFind($this->request->get['order_id']);

            if ($linked_order != false) {
                if ($this->config->get('etsy_order_status_paid') == $this->request->get['status_id']) {
                    $response = $this->openbay->etsy->orderUpdatePaid($linked_order['receipt_id'], "true");
                }

                if ($this->config->get('etsy_order_status_shipped') == $this->request->get['status_id']) {
                    $response = $this->openbay->etsy->orderUpdateShipped($linked_order['receipt_id'], "true");
                }
            }
        }
    }

    public function updateOrderInfo() {
        $json = array();

        /**
         * response options:
         * json['error'] = status DID NOT update
         * json['success'] = status UPDATED, just set value to true.
         * json['info'] = status UPDATED but messages or information were returned
         */

        $order_id = (int)$this->request->get['order_id'];
        $status_id = (int)$this->request->get['status_id'];
        $web_order = 1;

        if ($this->config->get('ebay_status') == 1 && $this->openbay->ebay->getOrder($order_id) !== false) {
            $web_order = 0;

            if ($this->config->get('ebay_status_shipped_id') == $status_id) {
                $response = $this->openbay->ebay->orderStatusListen($order_id, $status_id, array('tracking_no' => $this->request->post['tracking_no'], 'carrier_id' => $this->request->post['carrier_id']));
            } else {
                $response = $this->openbay->ebay->orderStatusListen($order_id, $status_id);
            }
        }

        if ($this->config->get('openbay_amazon_status') == 1 && $this->openbay->amazon->getOrder($order_id) !== false) {
            $web_order = 0;

            if ($this->config->get('openbay_amazon_order_status_shipped') == $status_id) {
                if (!empty($this->request->post['courier_other'])) {
                    $response = $this->openbay->amazon->updateOrder($order_id, 'shipped', $this->request->post['courier_other'], false, $this->request->post['tracking_no']);
                } else {
                    $response = $this->openbay->amazon->updateOrder($order_id, 'shipped', $this->request->post['courier_id'], true, $this->request->post['tracking_no']);
                }
            }

            if ($this->config->get('openbay_amazon_order_status_canceled') == $status_id) {
                $response = $this->openbay->amazon->updateOrder($order_id, 'canceled');
            }
        }

        if ($this->config->get('openbay_amazonus_status') == 1 && $this->openbay->amazonus->getOrder($order_id) !== false) {
            $web_order = 0;

            if ($this->config->get('openbay_amazonus_order_status_shipped') == $status_id) {
                if (!empty($this->request->post['courier_other'])) {
                    $response = $this->openbay->amazonus->updateOrder($order_id, 'shipped', $this->request->post['courier_other'], false, $this->request->post['tracking_no']);
                } else {
                    $response = $this->openbay->amazonus->updateOrder($order_id, 'shipped', $this->request->post['courier_id'], true, $this->request->post['tracking_no']);
                }
            }
            if ($this->config->get('openbay_amazonus_order_status_canceled') == $status_id) {
                $response = $this->openbay->amazonus->updateOrder($order_id, 'canceled');
            }
        }

        if ($this->config->get('etsy_status') == 1) {
            $linked_order = $this->openbay->etsy->orderFind($order_id);

            if ($linked_order != false) {
                $web_order = 0;

                if ($this->config->get('etsy_order_status_paid') == $status_id) {
                    $response = $this->openbay->etsy->orderUpdatePaid($linked_order['receipt_id'], "true");
                }

                if ($this->config->get('etsy_order_status_shipped') == $status_id) {
                    $response = $this->openbay->etsy->orderUpdateShipped($linked_order['receipt_id'], "true");
                }
            }
        }

        if ($web_order == 1) {
            // no need to check a web order here, skip
            $json['success'] = true;
        } else {



            // @todo
            // use the response from marketplace to determine success/fail
            $json['success'] = true;
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

	public function orderList() {
        $this->load->language('sale/order');
        $this->load->language('extension/openbay/openbay_order');

        $data = $this->language->all();

		$this->load->model('extension/openbay/order');

		$this->document->setTitle($this->language->get('heading_title'));
		$this->document->addScript('view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.js');
        $this->document->addStyle('view/javascript/jquery/datetimepicker/bootstrap-datetimepicker.min.css');

		if (isset($this->request->get['filter_order_id'])) {
			$filter_order_id = $this->request->get['filter_order_id'];
		} else {
			$filter_order_id = '';
		}

		if (isset($this->request->get['filter_customer'])) {
			$filter_customer = $this->request->get['filter_customer'];
		} else {
			$filter_customer = '';
		}

		if (isset($this->request->get['filter_order_status_id'])) {
			$filter_order_status_id = $this->request->get['filter_order_status_id'];
		} else {
			$filter_order_status_id = '';
		}

		if (isset($this->request->get['filter_date_added'])) {
			$filter_date_added = $this->request->get['filter_date_added'];
		} else {
			$filter_date_added = '';
		}

		if (isset($this->request->get['filter_channel'])) {
			$filter_channel = $this->request->get['filter_channel'];
		} else {
			$filter_channel = '';
		}

		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'o.order_id';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'DESC';
		}

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		$url = '';

		if (isset($this->request->get['filter_order_id'])) {
			$url .= '&filter_order_id=' . $this->request->get['filter_order_id'];
		}

		if (isset($this->request->get['filter_customer'])) {
			$url .= '&filter_customer=' . $this->request->get['filter_customer'];
		}

		if (isset($this->request->get['filter_order_status_id'])) {
			$url .= '&filter_order_status_id=' . $this->request->get['filter_order_status_id'];
		}

		if (isset($this->request->get['filter_date_added'])) {
			$url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
		}

		if (isset($this->request->get['filter_channel'])) {
			$url .= '&filter_channel=' . $this->request->get['filter_channel'];
		}

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'href'      => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
			'text'      => $this->language->get('text_home'),
		);

		$data['breadcrumbs'][] = array(
			'href' => $this->url->link('marketplace/openbay', 'user_token=' . $this->session->data['user_token'], true),
			'text' => $this->language->get('text_openbay'),
		);

		$data['breadcrumbs'][] = array(
			'href' => $this->url->link('marketplace/openbay/manage', 'user_token=' . $this->session->data['user_token'], true),
			'text' => $data['heading_title'],
		);

		$data['orders'] = array();

		$filter = array(
			'filter_order_id'        => $filter_order_id,
			'filter_customer'	     => $filter_customer,
			'filter_order_status_id' => $filter_order_status_id,
			'filter_date_added'      => $filter_date_added,
			'filter_channel'         => $filter_channel,
			'sort'                   => $sort,
			'order'                  => $order,
			'start'                  => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit'                  => $this->config->get('config_limit_admin')
		);

		$order_total = $this->model_extension_openbay_order->getTotalOrders($filter);
		$results = $this->model_extension_openbay_order->getOrders($filter);

		foreach ($results as $result) {
			$channel = $this->language->get('text_' . $result['channel']);

			$data['orders'][] = array(
				'order_id'      => $result['order_id'],
				'customer'      => $result['customer'],
				'status'        => $result['status'],
				'date_added'    => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
				'selected'      => isset($this->request->post['selected']) && in_array($result['order_id'], $this->request->post['selected']),
				'view'          => $this->url->link('sale/order/info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . $result['order_id'] . $url, true),
				'channel'       => $channel,
			);
		}

		$data['channels'] = array();

		$data['channels'][] = array(
			'module' => 'web',
			'title' => $this->language->get('text_web'),
		);

		if ($this->config->get('ebay_status')) {
			$data['channels'][] = array(
				'module' => 'ebay',
				'title' => $this->language->get('text_ebay'),
			);
		}

		if ($this->config->get('openbay_amazon_status')) {
			$data['channels'][] = array(
				'module' => 'amazon',
				'title' => $this->language->get('text_amazon'),
			);
		}

		if ($this->config->get('openbay_amazonus_status')) {
			$data['channels'][] = array(
				'module' => 'amazonus',
				'title' => $this->language->get('text_amazonus'),
			);
		}

		if ($this->config->get('etsy_status')) {
			$data['channels'][] = array(
				'module' => 'etsy',
				'title' => $this->language->get('text_etsy'),
			);
		}

		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_no_results'] = $this->language->get('text_no_results');
		$data['text_missing'] = $this->language->get('text_missing');
		$data['column_order_id'] = $this->language->get('column_order_id');
		$data['column_customer'] = $this->language->get('column_customer');
		$data['column_status'] = $this->language->get('column_status');
		$data['column_date_added'] = $this->language->get('column_date_added');
		$data['column_action'] = $this->language->get('column_action');
		$data['button_filter'] = $this->language->get('button_filter');

		$data['user_token'] = $this->session->data['user_token'];

		if (isset($this->session->data['error'])) {
			if (!is_array($this->session->data['error'])) {
				$this->session->data['error'] = array($this->session->data['error']);
			}

			$data['error_warning'] = $this->session->data['error'];
			unset($this->session->data['error']);
		} else {
			$data['error_warning'] = '';
		}

		$data['error_orders'] = '';

		if (isset($this->session->data['error_orders'])) {
			if (is_array($this->session->data['error_orders'])) {
				$data['error_orders'] = $this->session->data['error_orders'];
			}

			unset($this->session->data['error_orders']);
		}

		$data['success_orders'] = '';

		if (isset($this->session->data['success_orders'])) {
			if (is_array($this->session->data['success_orders'])) {
				$data['success_orders'] = $this->session->data['success_orders'];
			}

			unset($this->session->data['success_orders']);
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		$url = '';

		if (isset($this->request->get['filter_order_id'])) {
			$url .= '&filter_order_id=' . $this->request->get['filter_order_id'];
		}

		if (isset($this->request->get['filter_customer'])) {
			$url .= '&filter_customer=' . $this->request->get['filter_customer'];
		}

		if (isset($this->request->get['filter_order_status_id'])) {
			$url .= '&filter_order_status_id=' . $this->request->get['filter_order_status_id'];
		}

		if (isset($this->request->get['filter_date_added'])) {
			$url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
		}

		if (isset($this->request->get['filter_channel'])) {
			$url .= '&filter_channel=' . $this->request->get['filter_channel'];
		}

		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['sort_order'] = $this->url->link('marketplace/openbay/orderlist', 'user_token=' . $this->session->data['user_token'] . '&sort=o.order_id' . $url, true);
		$data['sort_customer'] = $this->url->link('marketplace/openbay/orderlist', 'user_token=' . $this->session->data['user_token'] . '&sort=customer' . $url, true);
		$data['sort_status'] = $this->url->link('marketplace/openbay/orderlist', 'user_token=' . $this->session->data['user_token'] . '&sort=status' . $url, true);
		$data['sort_date_added'] = $this->url->link('marketplace/openbay/orderlist', 'user_token=' . $this->session->data['user_token'] . '&sort=o.date_added' . $url, true);
		$data['sort_channel'] = $this->url->link('marketplace/openbay/orderlist', 'user_token=' . $this->session->data['user_token'] . '&sort=channel' . $url, true);
		$data['link_update'] = $this->url->link('marketplace/openbay/orderlistupdate', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['cancel'] = $this->url->link('marketplace/openbay', 'user_token=' . $this->session->data['user_token'], true);

		$url = '';

		if (isset($this->request->get['filter_order_id'])) {
			$url .= '&filter_order_id=' . $this->request->get['filter_order_id'];
		}

		if (isset($this->request->get['filter_customer'])) {
			$url .= '&filter_customer=' . $this->request->get['filter_customer'];
		}

		if (isset($this->request->get['filter_order_status_id'])) {
			$url .= '&filter_order_status_id=' . $this->request->get['filter_order_status_id'];
		}

		if (isset($this->request->get['filter_date_added'])) {
			$url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
		}

		if (isset($this->request->get['filter_channel'])) {
			$url .= '&filter_channel=' . $this->request->get['filter_channel'];
		}

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$pagination = new Pagination();
		$pagination->total = $order_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->text = $this->language->get('text_pagination');
		$pagination->url = $this->url->link('marketplace/openbay/orderlist', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($order_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($order_total - $this->config->get('config_limit_admin'))) ? $order_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $order_total, ceil($order_total / $this->config->get('config_limit_admin')));

		$data['filter_order_id'] = $filter_order_id;
		$data['filter_customer'] = $filter_customer;
		$data['filter_order_status_id'] = $filter_order_status_id;
		$data['filter_date_added'] = $filter_date_added;
		$data['filter_channel'] = $filter_channel;

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		$data['sort'] = $sort;
		$data['order'] = $order;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/openbay/openbay_orderlist', $data));
	}

	public function orderListUpdate() {
		if (!isset($this->request->post['selected']) || empty($this->request->post['selected'])) {
            $this->load->language('extension/openbay/openbay_order');
            $data = $this->language->all();

			$this->session->data['error'] = $data['text_no_orders'];
			$this->response->redirect($this->url->link('marketplace/openbay/orderlist', 'user_token=' . $this->session->data['user_token'], true));
		} else {
            $this->load->language('sale/order');
            $this->load->language('extension/openbay/openbay_order');

            $data = $this->language->all();

            $this->document->setTitle($this->language->get('heading_title'));

            $data['breadcrumbs'] = array();

            $data['breadcrumbs'][] = array(
                'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
                'text' => $this->language->get('text_home'),
            );

            $data['breadcrumbs'][] = array(
                'href' => $this->url->link('marketplace/openbay', 'user_token=' . $this->session->data['user_token'], true),
                'text' => $this->language->get('text_openbay'),
            );

            $data['breadcrumbs'][] = array(
                'href' => $this->url->link('marketplace/openbay/orderlist', 'user_token=' . $this->session->data['user_token'], true),
                'text' => $data['heading_title'],
            );

			$this->load->model('extension/openbay/order');


			$data['market_options'] = array();

			if ($this->config->get('ebay_status') == 1) {
				$data['market_options']['ebay']['carriers'] = $this->openbay->ebay->getCarriers();
			}

			if ($this->config->get('openbay_amazon_status') == 1) {
				$data['market_options']['amazon']['carriers'] = $this->openbay->amazon->getCarriers();
				$data['market_options']['amazon']['default_carrier'] = $this->config->get('openbay_amazon_default_carrier');
			}

			if ($this->config->get('openbay_amazonus_status') == 1) {
				$data['market_options']['amazonus']['carriers'] = $this->openbay->amazonus->getCarriers();
			}

			$this->load->model('localisation/order_status');

			$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
			$data['status_mapped'] = array();

			foreach($data['order_statuses'] as $status) {
				$data['status_mapped'][$status['order_status_id']] = $status['name'];
			}

			$orders = array();

			foreach($this->request->post['selected'] as $order_id) {
				$order = $this->model_extension_openbay_order->getOrder($order_id);

				if ($order['order_status_id'] != $this->request->post['change_order_status_id']) {
					$order['channel'] = $this->language->get('text_' . $order['channel']);
					$order['view_order_link'] = $this->url->link('sale/order/info', 'user_token=' . $this->session->data['user_token'] . '&order_id=' . (int)$order_id, true);
					$orders[] = $order;
				}
			}

			if (empty($orders)) {
				$this->session->data['error'] = $data['text_no_orders'];
				$this->response->redirect($this->url->link('marketplace/openbay/orderlist', 'user_token=' . $this->session->data['user_token'], true));
			} else {
				$data['orders'] = $orders;
			}

			$data['order_count'] = count($data['orders']);

			$data['change_order_status_id'] = $this->request->post['change_order_status_id'];
			$data['ebay_status_shipped_id'] = $this->config->get('ebay_status_shipped_id');
			$data['openbay_amazon_order_status_shipped'] = $this->config->get('openbay_amazon_order_status_shipped');
			$data['openbay_amazonus_order_status_shipped'] = $this->config->get('openbay_amazonus_order_status_shipped');

			$data['header'] = $this->load->controller('common/header');
			$data['column_left'] = $this->load->controller('common/column_left');
			$data['footer'] = $this->load->controller('common/footer');

			$url = '';

			if (isset($this->request->get['filter_order_id'])) {
				$url .= '&filter_order_id=' . $this->request->get['filter_order_id'];
			}

			if (isset($this->request->get['filter_customer'])) {
				$url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['filter_order_status_id'])) {
				$url .= '&filter_order_status_id=' . $this->request->get['filter_order_status_id'];
			}

			if (isset($this->request->get['filter_channel'])) {
				$url .= '&filter_channel=' . $this->request->get['filter_channel'];
			}

			if (isset($this->request->get['filter_date_added'])) {
				$url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
			}

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

            // API login
            $data['catalog'] = $this->request->server['HTTPS'] ? HTTPS_CATALOG : HTTP_CATALOG;

            // API login
            $this->load->model('user/api');

            $api_info = $this->model_user_api->getApi($this->config->get('config_api_id'));

            if ($api_info && $this->user->hasPermission('modify', 'sale/order')) {
                $session = new Session($this->config->get('session_engine'), $this->registry);

                $session->start();

                $this->model_user_api->deleteApiSessionBySessonId($session->getId());

                $this->model_user_api->addApiSession($api_info['api_id'], $session->getId(), $this->request->server['REMOTE_ADDR']);

                $session->data['api_id'] = $api_info['api_id'];

                $data['api_token'] = $session->getId();
            } else {
                $data['api_token'] = '';

                // @todo if cannot get token then redirect and show error



            }

            $data['user_token'] = $this->session->data['user_token'];

            $data['link_complete'] = $this->url->link('marketplace/openbay/orderlistcomplete', 'user_token=' . $this->session->data['user_token'], true);
			$data['cancel'] = $this->url->link('marketplace/openbay/orderlist', 'user_token=' . $this->session->data['user_token'] . $url, true);

			$this->response->setOutput($this->load->view('extension/openbay/openbay_orderlist_confirm', $data));
		}
	}

	public function orderListComplete() {
		$this->load->model('sale/order');
		$this->load->model('extension/openbay/openbay');
		$this->load->model('localisation/order_status');

		$this->load->language('extension/openbay/openbay_order');

        // API login
        $this->load->model('user/api');

        $api_info = $this->model_user_api->getApi($this->config->get('config_api_id'));

        if ($api_info) {
            $session = new Session($this->config->get('session_engine'), $this->registry);

            $session->start();

            $this->model_user_api->deleteApiSessionBySessonId($session->getId());

            $this->model_user_api->addApiSession($api_info['api_id'], $session->getId(), "127.0.0.1");

            $session->data['api_id'] = $api_info['api_id'];

            $api_token = $session->getId();
        } else {
            $this->session->data['error'] = $this->language->get('error_fetch_api_id');
            $this->response->redirect($this->url->link('marketplace/openbay/orderList', 'user_token=' . $this->session->data['user_token'], true));
        }

        //Amazon EU
        if ($this->config->get('openbay_amazon_status') == 1) {
            $this->load->model('extension/openbay/amazon');

            $orders = array();

            foreach ($this->request->post['order_id'] as $order_id) {
                if ($this->request->post['channel'][$order_id] == 'Amazon EU') {
                    if ($this->config->get('openbay_amazon_order_status_shipped') == $this->request->post['order_status_id']) {
                        if (isset($this->request->post['carrier_other'][$order_id]) && !empty($this->request->post['carrier_other'][$order_id])) {
                            $carrier_from_list = false;
                            $carrier = $this->request->post['carrier_other'][$order_id];
                        } else {
                            $carrier_from_list = true;
                            $carrier = $this->request->post['carrier'][$order_id];
                        }

                        $orders[] = array(
                            'order_id' => $order_id,
                            'status' => 'shipped',
                            'carrier' => $carrier,
                            'carrier_from_list' => $carrier_from_list,
                            'tracking' => $this->request->post['tracking'][$order_id],
                        );

                        $this->model_extension_openbay_amazon->updateAmazonOrderTracking($order_id, $carrier, $carrier_from_list, !empty($carrier) ? $this->request->post['tracking'][$order_id] : '');
                    }

                    if ($this->config->get('openbay_amazon_order_status_canceled') == $this->request->post['order_status_id']) {
                        $orders[] = array(
                            'order_id' => $order_id,
                            'status' => 'canceled',
                        );
                    }
                }
            }

            if ($orders) {
                $this->openbay->amazon->bulkUpdateOrders($orders);
            }
        }

        //Amazon US
        if ($this->config->get('openbay_amazonus_status') == 1) {
            $this->load->model('extension/openbay/amazonus');

            $orders = array();

            foreach ($this->request->post['order_id'] as $order_id) {
                if ($this->request->post['channel'][$order_id] == 'Amazon US') {
                    if ($this->config->get('openbay_amazonus_order_status_shipped') == $this->request->post['order_status_id']) {
                        $carrier = '';

                        if (isset($this->request->post['carrier_other'][$order_id]) && !empty($this->request->post['carrier_other'][$order_id])) {
                            $carrier_from_list = false;
                            $carrier = $this->request->post['carrier_other'][$order_id];
                        } else {
                            $carrier_from_list = true;
                            $carrier = $this->request->post['carrier'][$order_id];
                        }

                        $orders[] = array(
                            'order_id' => $order_id,
                            'status' => 'shipped',
                            'carrier' => $carrier,
                            'carrier_from_list' => $carrier_from_list,
                            'tracking' => $this->request->post['tracking'][$order_id],
                        );

                        $this->model_extension_openbay_amazonus->updateAmazonusOrderTracking($order_id, $carrier, $carrier_from_list, !empty($carrier) ? $this->request->post['tracking'][$order_id] : '');
                    }

                    if ($this->config->get('openbay_amazonus_order_status_canceled') == $this->request->post['order_status_id']) {
                        $orders[] = array(
                            'order_id' => $order_id,
                            'status' => 'canceled',
                        );
                    }
                }
            }

            if ($orders) {
                $this->openbay->amazonus->bulkUpdateOrders($orders);
            }
        }

        $i = 0;

        foreach ($this->request->post['order_id'] as $order_id) {
            if ($this->config->get('ebay_status') == 1 && $this->request->post['channel'][$order_id] == 'eBay') {
                if ($this->config->get('ebay_status_shipped_id') == $this->request->post['order_status_id']) {
                    $this->openbay->ebay->orderStatusListen($order_id, $this->request->post['order_status_id'], array('tracking_no' => $this->request->post['tracking'][$order_id], 'carrier_id' => $this->request->post['carrier'][$order_id]));
                } else {
                    $this->openbay->ebay->orderStatusListen($order_id, $this->request->post['order_status_id']);
                }
            }

            if ($this->config->get('etsy_status') == 1 && $this->request->post['channel'][$order_id] == 'Etsy') {
                $linked_order = $this->openbay->etsy->orderFind($order_id);

                if ($linked_order != false) {
                    if ($this->config->get('etsy_order_status_paid') == $this->request->post['order_status_id']) {
                        $response = $this->openbay->etsy->orderUpdatePaid($linked_order['receipt_id'], "true");
                    }

                    if ($this->config->get('etsy_order_status_shipped') == $this->request->post['order_status_id']) {
                        $response = $this->openbay->etsy->orderUpdateShipped($linked_order['receipt_id'], "true");
                    }
                }
            }

            $data = array(
                'notify' => $this->request->post['notify'][$order_id],
                'order_status_id' => $this->request->post['order_status_id'],
                'comment' => $this->request->post['comments'][$order_id],
                'override' => 1,
            );

            $add_history = $this->model_extension_openbay_openbay->addOrderHistory($order_id, $data, $api_token);

            if (isset($add_history['error'])) {
                $this->session->data['error_orders'][] = array(
                    'order_id' => $order_id,
                    'error' => $add_history['error']
                );
            }

            if (isset($add_history['success'])) {
                $this->session->data['success_orders'][] = array(
                    'order_id' => $order_id,
                    'success' => $add_history['success']
                );
            }

            $i++;
        }

        $this->session->data['success'] = sprintf($this->language->get('text_confirmed'), $i);


		$this->response->redirect($this->url->link('marketplace/openbay/orderlist', 'user_token=' . $this->session->data['user_token'], true));
	}

	public function items() {
		$this->load->language('catalog/product');
		$this->load->language('extension/openbay/openbay_itemlist');

		$data = $this->language->all();

		$this->document->setTitle($this->language->get('heading_title'));
        $this->document->addScript('view/javascript/openbay/js/openbay.js');
        $this->document->addScript('view/javascript/openbay/js/faq.js');

		$this->load->model('catalog/product');
		$this->load->model('catalog/category');
		$this->load->model('catalog/manufacturer');
		$this->load->model('extension/openbay/openbay');
		$this->load->model('tool/image');

		if ($this->openbay->addonLoad('openstock')) {
			$this->load->model('extension/module/openstock');
			$openstock_installed = true;
		} else {
			$openstock_installed = false;
		}

		$data['category_list'] = $this->model_catalog_category->getCategories(array());
		$data['manufacturer_list'] = $this->model_catalog_manufacturer->getManufacturers(array());

		if (isset($this->request->get['filter_name'])) {
			$filter_name = $this->request->get['filter_name'];
		} else {
			$filter_name = '';
		}

		if (isset($this->request->get['filter_model'])) {
			$filter_model = $this->request->get['filter_model'];
		} else {
			$filter_model = '';
		}

		if (isset($this->request->get['filter_price'])) {
			$filter_price = $this->request->get['filter_price'];
		} else {
			$filter_price = '';
		}

		if (isset($this->request->get['filter_price_to'])) {
			$filter_price_to = $this->request->get['filter_price_to'];
		} else {
			$filter_price_to = '';
		}

		if (isset($this->request->get['filter_quantity'])) {
			$filter_quantity = $this->request->get['filter_quantity'];
		} else {
			$filter_quantity = '';
		}

		if (isset($this->request->get['filter_quantity_to'])) {
			$filter_quantity_to = $this->request->get['filter_quantity_to'];
		} else {
			$filter_quantity_to = '';
		}

		if (isset($this->request->get['filter_status'])) {
			$filter_status = $this->request->get['filter_status'];
		} else {
			$filter_status = '';
		}

		if (isset($this->request->get['filter_sku'])) {
			$filter_sku = $this->request->get['filter_sku'];
		} else {
			$filter_sku = '';
		}

		if (isset($this->request->get['filter_desc'])) {
			$filter_desc = $this->request->get['filter_desc'];
		} else {
			$filter_desc = '';
		}

		if (isset($this->request->get['filter_category'])) {
			$filter_category = $this->request->get['filter_category'];
		} else {
			$filter_category = '';
		}

		if (isset($this->request->get['filter_manufacturer'])) {
			$filter_manufacturer = $this->request->get['filter_manufacturer'];
		} else {
			$filter_manufacturer = '';
		}

		if (isset($this->request->get['filter_marketplace'])) {
			$filter_marketplace = $this->request->get['filter_marketplace'];
		} else {
			$filter_marketplace = '';
		}

		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'pd.name';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'ASC';
		}

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		$url = '';

		if (isset($this->request->get['filter_name'])) {
			$url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_model'])) {
			$url .= '&filter_model=' . urlencode(html_entity_decode($this->request->get['filter_model'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_price'])) {
			$url .= '&filter_price=' . $this->request->get['filter_price'];
		}

		if (isset($this->request->get['filter_price_to'])) {
			$url .= '&filter_price_to=' . $this->request->get['filter_price_to'];
		}

		if (isset($this->request->get['filter_quantity'])) {
			$url .= '&filter_quantity=' . $this->request->get['filter_quantity'];
		}

		if (isset($this->request->get['filter_quantity_to'])) {
			$url .= '&filter_quantity_to=' . $this->request->get['filter_quantity_to'];
		}

		if (isset($this->request->get['filter_status'])) {
			$url .= '&filter_status=' . $this->request->get['filter_status'];
		}

		if (isset($this->request->get['filter_sku'])) {
			$url .= '&filter_sku=' . $this->request->get['filter_sku'];
		}

		if (isset($this->request->get['filter_desc'])) {
			$url .= '&filter_desc=' . $this->request->get['filter_desc'];
		}

		if (isset($this->request->get['filter_category'])) {
			$url .= '&filter_category=' . $this->request->get['filter_category'];
		}

		if (isset($this->request->get['filter_manufacturer'])) {
			$url .= '&filter_manufacturer=' . $this->request->get['filter_manufacturer'];
		}

		if (isset($this->request->get['filter_marketplace'])) {
			$url .= '&filter_marketplace=' . $this->request->get['filter_marketplace'];
		}

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
		);

		$data['breadcrumbs'][] = array(
			'text' 		=> $this->language->get('text_openbay'),
			'href' 		=> $this->url->link('marketplace/openbay', 'user_token=' . $this->session->data['user_token'], true),
		);

		$data['breadcrumbs'][] = array(
			'text'      => $this->language->get('heading_title'),
			'href'      => $this->url->link('marketplace/openbay/items', 'user_token=' . $this->session->data['user_token'] . $url, true),
		);

		if ($this->config->get('openbay_amazon_status')) {
			$data['link_amazon_eu_bulk'] = $this->url->link('extension/openbay/amazon/bulkListProducts', 'user_token=' . $this->session->data['user_token'] . $url, true);
		} else {
			$data['link_amazon_eu_bulk'] = '';
		}

		if ($this->config->get('openbay_amazonus_status')) {
			$data['link_amazon_us_bulk'] = $this->url->link('extension/openbay/amazonus/bulkListProducts', 'user_token=' . $this->session->data['user_token'] . $url, true);
		} else {
			$data['link_amazon_us_bulk'] = '';
		}

		if ($this->config->get('ebay_status') == '1') {
			$data['link_ebay_bulk'] = $this->url->link('extension/openbay/openbay/createBulk', 'user_token=' . $this->session->data['user_token'], true);
		} else {
			$data['link_ebay_bulk'] = '';
		}

		$data['products'] = array();

		$filter_market_id = '';
		$filter_market_name = '';

		$ebay_status = array(
			0 => 'ebay_inactive',
			1 => 'ebay_active',
		);

		if (in_array($filter_marketplace, $ebay_status)) {
			$filter_market_name = 'ebay';
			$filter_market_id = array_search($filter_marketplace, $ebay_status);
		}

		$amazon_status = array(
			0 => 'amazon_unlisted',
			1 => 'amazon_saved',
			2 => 'amazon_uploaded',
			3 => 'amazon_ok',
			4 => 'amazon_error',
			5 => 'amazon_linked',
			6 => 'amazon_not_linked',
		);

		if (in_array($filter_marketplace, $amazon_status)) {
			$filter_market_name = 'amazon';
			$filter_market_id = array_search($filter_marketplace, $amazon_status);
		}

		$amazonus_status = array(
			0 => 'amazonus_unlisted',
			1 => 'amazonus_saved',
			2 => 'amazonus_uploaded',
			3 => 'amazonus_ok',
			4 => 'amazonus_error',
			5 => 'amazonus_linked',
			6 => 'amazonus_not_linked',
		);

		if (in_array($filter_marketplace, $amazonus_status)) {
			$filter_market_name = 'amazonus';
			$filter_market_id = array_search($filter_marketplace, $amazonus_status);
		}

		$filter = array(
			'filter_name'	        => $filter_name,
			'filter_model'	        => $filter_model,
			'filter_price'	        => $filter_price,
			'filter_price_to'	    => $filter_price_to,
			'filter_quantity'       => $filter_quantity,
			'filter_quantity_to'    => $filter_quantity_to,
			'filter_status'         => $filter_status,
			'filter_sku'            => $filter_sku,
			'filter_desc'           => $filter_desc,
			'filter_category'       => $filter_category,
			'filter_manufacturer'   => $filter_manufacturer,
			'filter_market_name'    => $filter_market_name,
			'filter_market_id'      => $filter_market_id,
			'sort'                  => $sort,
			'order'                 => $order,
			'start'                 => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit'                 => $this->config->get('config_limit_admin')
		);

		if ($this->config->get('ebay_status') != '1' && $filter['filter_market_name'] == 'ebay') {
			$this->response->redirect($this->url->link('marketplace/openbay/items', 'user_token=' . $this->session->data['user_token'], true));
			return;
		}

		if ($this->config->get('openbay_amazon_status') != '1' && $filter['filter_market_name'] == 'amazon') {
			$this->response->redirect($this->url->link('marketplace/openbay/items', 'user_token=' . $this->session->data['user_token'], true));
			return;
		}

		if ($this->config->get('openbay_amazonus_status') != '1' && $filter['filter_market_name'] == 'amazonus') {
			$this->response->redirect($this->url->link('marketplace/openbay/items', 'user_token=' . $this->session->data['user_token'], true));
			return;
		}

		if ($this->config->get('etsy_status') != '1' && $filter['filter_market_name'] == 'etsy') {
			$this->response->redirect($this->url->link('marketplace/openbay/items', 'user_token=' . $this->session->data['user_token'], true));
			return;
		}

		$data['marketplace_statuses'] = array(
			'ebay' => $this->config->get('ebay_status'),
			'amazon' => $this->config->get('openbay_amazon_status'),
			'amazonus' => $this->config->get('openbay_amazonus_status'),
			'etsy' => $this->config->get('etsy_status'),
		);

		$product_total = $this->model_extension_openbay_openbay->getTotalProducts($filter);

		$results = $this->model_extension_openbay_openbay->getProducts($filter);

		foreach ($results as $result) {
			$edit = $this->url->link('catalog/product/edit', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $result['product_id'] . $url, true);

			if ($result['image'] && file_exists(DIR_IMAGE . $result['image'])) {
				$image = $this->model_tool_image->resize($result['image'], 40, 40);
			} else {
				$image = $this->model_tool_image->resize('no_image.png', 40, 40);
			}

			$special = false;

			$product_specials = $this->model_catalog_product->getProductSpecials($result['product_id']);

			foreach ($product_specials  as $product_special) {
				if (($product_special['date_start'] == '0000-00-00' || $product_special['date_start'] < date('Y-m-d')) && ($product_special['date_end'] == '0000-00-00' || $product_special['date_end'] > date('Y-m-d'))) {
					$special = $product_special['price'];

					break;
				}
			}

			/**
			 * Button status key:
			 * 0 = Inactive / no link to market
			 * 1 = Active
			 * 2 = Error
			 * 3 = Pending
			 */

			$markets = array();

			if ($this->config->get('ebay_status') == '1') {
				$this->load->model('extension/openbay/ebay');

				$active_list = $this->model_extension_openbay_ebay->getLiveListingArray();

				if (!array_key_exists($result['product_id'], $active_list)) {
					$markets[] = array(
						'name'      => $this->language->get('text_ebay'),
						'text'      => $this->language->get('button_add'),
						'href'      => $this->url->link('extension/openbay/ebay/create', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $result['product_id'] . $url, true),
						'status'	=> 0
					);
				} else {
					$markets[] = array(
						'name'      => $this->language->get('text_ebay'),
						'text'      => $this->language->get('button_edit'),
						'href'      => $this->url->link('extension/openbay/ebay/edit', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $result['product_id'] . $url, true),
						'status'	=> 1
					);
				}
			}

			if ($this->config->get('openbay_amazon_status') == '1') {
				$this->load->model('extension/openbay/amazon');
				$amazon_status = $this->model_extension_openbay_amazon->getProductStatus($result['product_id']);

                if ($amazon_status == 'processing') {
                    $markets[] = array(
                        'name'      => $this->language->get('text_amazon'),
                        'text'      => $this->language->get('text_processing'),
                        'href'      => '',
                        'status'	=> 3
                    );
                } else if ($amazon_status == 'linked' || $amazon_status == 'ok') {
                    $markets[] = array(
                        'name'      => $this->language->get('text_amazon'),
                        'text'      => $this->language->get('button_edit'),
                        'href'      => $this->url->link('extension/openbay/amazon_listing/edit', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $result['product_id'] . $url),
                        'status'	=> 1
                    );
                } else if ($amazon_status == 'saved') {
                    $markets[] = array(
                        'name'      => $this->language->get('text_amazon'),
                        'text'      => $this->language->get('button_edit'),
                        'href'      => $this->url->link('extension/openbay/amazon/savedListings', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $result['product_id'] . $url, true),
                        'status'	=> 4
                    );
                } else if ($amazon_status == 'error_quick' || $amazon_status == 'error_advanced' || $amazon_status == 'error_few') {
                    $markets[] = array(
                        'name'      => $this->language->get('text_amazon'),
                        'text'      => $this->language->get('button_error_fix'),
                        'href'      => $this->url->link('extension/openbay/amazon_listing/create', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $result['product_id'] . $url),
                        'status'	=> 2
                    );
                } else {
                    $markets[] = array(
                        'name'      => $this->language->get('text_amazon'),
                        'text'      => $this->language->get('button_add'),
                        'href'      => $this->url->link('extension/openbay/amazon_listing/create', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $result['product_id'] . $url),
                        'status'	=> 0
                    );
                }
			}

			if ($this->config->get('openbay_amazonus_status') == '1') {
				$this->load->model('extension/openbay/amazonus');
				$amazonus_status = $this->model_extension_openbay_amazonus->getProductStatus($result['product_id']);

				if ($amazonus_status == 'processing') {
					$markets[] = array(
						'name'      => $this->language->get('text_amazonus'),
						'text'      => $this->language->get('text_processing'),
						'href'      => '',
						'status'	=> 3
					);
				} else if ($amazonus_status == 'linked' || $amazonus_status == 'ok') {
					$markets[] = array(
						'name'      => $this->language->get('text_amazonus'),
						'text'      => $this->language->get('button_edit'),
						'href'      => $this->url->link('extension/openbay/amazonus_listing/edit', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $result['product_id'] . $url, true),
						'status'	=> 1
					);
                } else if ($amazonus_status == 'saved') {
                    $markets[] = array(
                        'name'      => $this->language->get('text_amazon'),
                        'text'      => $this->language->get('button_edit'),
                        'href'      => $this->url->link('extension/openbay/amazonus/savedListings', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $result['product_id'] . $url, true),
                        'status'	=> 4
                    );
				} else if ($amazonus_status == 'error_quick' || $amazonus_status == 'error_advanced' || $amazonus_status == 'error_few') {
					$markets[] = array(
						'name'      => $this->language->get('text_amazonus'),
						'text'      => $this->language->get('button_error_fix'),
						'href'      => $this->url->link('extension/openbay/amazonus_listing/create', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $result['product_id'] . $url, true),
						'status'	=> 2
					);
				} else {
					$markets[] = array(
						'name'      => $this->language->get('text_amazonus'),
						'text'      => $this->language->get('button_add'),
						'href'      => $this->url->link('extension/openbay/amazonus_listing/create', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $result['product_id'] . $url, true),
						'status'	=> 0
					);
				}
			}

			if ($this->config->get('etsy_status') == '1') {
				$this->load->model('extension/openbay/etsy_product');

				$status = $this->model_extension_openbay_etsy_product->getStatus($result['product_id']);

				if ($status == 0) {
					$markets[] = array(
						'name'      => $this->language->get('text_etsy'),
						'text'      => $this->language->get('button_add'),
						'href'      => $this->url->link('extension/openbay/etsy_product/create', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $result['product_id'] . $url, true),
						'status'	=> 0
					);
				} else {
					$markets[] = array(
						'name'      => $this->language->get('text_etsy'),
						'text'      => $this->language->get('button_edit'),
						'href'      => $this->url->link('extension/openbay/etsy_product/edit', 'user_token=' . $this->session->data['user_token'] . '&product_id=' . $result['product_id'] . $url, true),
						'status'	=> 1
					);
				}
			}

			if (!isset($result['has_option'])) {
				$result['has_option'] = 0;
			}

			$data['products'][] = array(
				'markets'   => $markets,
				'product_id' => $result['product_id'],
				'name'       => $result['name'],
				'model'      => $result['model'],
				'price'      => $result['price'],
				'special'    => $special,
				'image'      => $image,
				'quantity'   => $result['quantity'],
				'status'     => ($result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled')),
				'selected'   => isset($this->request->post['selected']) && in_array($result['product_id'], $this->request->post['selected']),
				'edit'       => $edit,
				'has_option' => $openstock_installed ? $result['has_option'] : 0,
				'vCount'     => $openstock_installed ? $this->model_setting_module_openstock->countVariation($result['product_id']) : '',
				'vsCount'    => $openstock_installed ? $this->model_setting_module_openstock->countVariationStock($result['product_id']) : '',
			);
		}

		$data['user_token'] = $this->session->data['user_token'];

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			if (isset($this->session->data['warning'])) {
				$data['error_warning'] = $this->session->data['warning'];
				unset($this->session->data['warning']);
			} else {
				$data['error_warning'] = '';
			}
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		$url = '';

		if (isset($this->request->get['filter_name'])) {
			$url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_model'])) {
			$url .= '&filter_model=' . urlencode(html_entity_decode($this->request->get['filter_model'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_price'])) {
			$url .= '&filter_price=' . $this->request->get['filter_price'];
		}

		if (isset($this->request->get['filter_price_to'])) {
			$url .= '&filter_price_to=' . $this->request->get['filter_price_to'];
		}

		if (isset($this->request->get['filter_quantity'])) {
			$url .= '&filter_quantity=' . $this->request->get['filter_quantity'];
		}

		if (isset($this->request->get['filter_quantity_to'])) {
			$url .= '&filter_quantity_to=' . $this->request->get['filter_quantity_to'];
		}

		if (isset($this->request->get['filter_status'])) {
			$url .= '&filter_status=' . $this->request->get['filter_status'];
		}

		if (isset($this->request->get['filter_sku'])) {
			$url .= '&filter_sku=' . $this->request->get['filter_sku'];
		}

		if (isset($this->request->get['filter_desc'])) {
			$url .= '&filter_desc=' . $this->request->get['filter_desc'];
		}

		if (isset($this->request->get['filter_category'])) {
			$url .= '&filter_category=' . $this->request->get['filter_category'];
		}

		if (isset($this->request->get['filter_manufacturer'])) {
			$url .= '&filter_manufacturer=' . $this->request->get['filter_manufacturer'];
		}

		if (isset($this->request->get['filter_marketplace'])) {
			$url .= '&filter_marketplace=' . $this->request->get['filter_marketplace'];
		}

		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['sort_name'] = $this->url->link('marketplace/openbay/items', 'user_token=' . $this->session->data['user_token'] . '&sort=pd.name' . $url, true);
		$data['sort_model'] = $this->url->link('marketplace/openbay/items', 'user_token=' . $this->session->data['user_token'] . '&sort=p.model' . $url, true);
		$data['sort_price'] = $this->url->link('marketplace/openbay/items', 'user_token=' . $this->session->data['user_token'] . '&sort=p.price' . $url, true);
		$data['sort_quantity'] = $this->url->link('marketplace/openbay/items', 'user_token=' . $this->session->data['user_token'] . '&sort=p.quantity' . $url, true);
		$data['sort_status'] = $this->url->link('marketplace/openbay/items', 'user_token=' . $this->session->data['user_token'] . '&sort=p.status' . $url, true);
		$data['sort_order'] = $this->url->link('marketplace/openbay/items', 'user_token=' . $this->session->data['user_token'] . '&sort=p.sort_order' . $url, true);

		$url = '';

		if (isset($this->request->get['filter_name'])) {
			$url .= '&filter_name=' . urlencode(html_entity_decode($this->request->get['filter_name'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_model'])) {
			$url .= '&filter_model=' . urlencode(html_entity_decode($this->request->get['filter_model'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_price'])) {
			$url .= '&filter_price=' . $this->request->get['filter_price'];
		}

		if (isset($this->request->get['filter_price_to'])) {
			$url .= '&filter_price_to=' . $this->request->get['filter_price_to'];
		}

		if (isset($this->request->get['filter_quantity'])) {
			$url .= '&filter_quantity=' . $this->request->get['filter_quantity'];
		}

		if (isset($this->request->get['filter_quantity_to'])) {
			$url .= '&filter_quantity_to=' . $this->request->get['filter_quantity_to'];
		}

		if (isset($this->request->get['filter_status'])) {
			$url .= '&filter_status=' . $this->request->get['filter_status'];
		}

		if (isset($this->request->get['filter_sku'])) {
			$url .= '&filter_sku=' . $this->request->get['filter_sku'];
		}

		if (isset($this->request->get['filter_desc'])) {
			$url .= '&filter_desc=' . $this->request->get['filter_desc'];
		}

		if (isset($this->request->get['filter_category'])) {
			$url .= '&filter_category=' . $this->request->get['filter_category'];
		}

		if (isset($this->request->get['filter_manufacturer'])) {
			$url .= '&filter_manufacturer=' . $this->request->get['filter_manufacturer'];
		}

		if (isset($this->request->get['filter_marketplace'])) {
			$url .= '&filter_marketplace=' . $this->request->get['filter_marketplace'];
		}

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$pagination = new Pagination();
		$pagination->total = $product_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->text = $this->language->get('text_pagination');
		$pagination->url = $this->url->link('marketplace/openbay/items', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($product_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($product_total - $this->config->get('config_limit_admin'))) ? $product_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $product_total, ceil($product_total / $this->config->get('config_limit_admin')));

		$data['filter_name'] = $filter_name;
		$data['filter_model'] = $filter_model;
		$data['filter_price'] = $filter_price;
		$data['filter_price_to'] = $filter_price_to;
		$data['filter_quantity'] = $filter_quantity;
		$data['filter_quantity_to'] = $filter_quantity_to;
		$data['filter_status'] = $filter_status;
		$data['filter_sku'] = $filter_sku;
		$data['filter_desc'] = $filter_desc;
		$data['filter_category'] = $filter_category;
		$data['filter_manufacturer'] = $filter_manufacturer;
		$data['filter_marketplace'] = $filter_marketplace;

		$data['sort'] = $sort;
		$data['order'] = $order;

		$data['ebay_status'] = $this->config->get('ebay_status');
		$data['user_token'] = $this->request->get['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/openbay/openbay_itemlist', $data));
	}

	public function itemlist() {
		$this->response->redirect($this->url->link('marketplace/openbay/items', 'user_token=' . $this->session->data['user_token'], true));
	}

	public function eventDeleteProduct($route, $data) {
		$this->openbay->log('eventDeleteProduct fired: ' . $route);

		foreach ($this->openbay->installed_markets as $market) {
			if ($market == 'amazon') {
				$status = $this->config->get('openbay_amazon_status');
			} elseif ($market == 'amazonus') {
				$status = $this->config->get('openbay_amazonus_status');
			} else {
				$status = $this->config->get($market . '_status');
			}

			if ($status == 1) {
				$this->openbay->{$market}->deleteProduct((int)$data[0]);
			}
		}
	}

	public function eventEditProduct($route, $data) {
		$this->openbay->log('eventEditProduct fired: ' . $route);

		foreach ($this->openbay->installed_markets as $market) {
			if ($market == 'amazon') {
				$status = $this->config->get('openbay_amazon_status');
			} elseif ($market == 'amazonus') {
				$status = $this->config->get('openbay_amazonus_status');
			} else {
				$status = $this->config->get($market . '_status');
			}

			if ($status == 1) {
				$this->openbay->{$market}->productUpdateListen((int)$data[0], $data[1]);
			}
		}
	}

	public function eventMenu($route, &$data) {
		// OpenBay Pro Menu
		$openbay_menu = array();

		$this->load->language('extension/openbay/openbay_menu');

		if ($this->user->hasPermission('access', 'marketplace/openbay')) {
			$openbay_menu[] = array(
				'name'	   => $this->language->get('text_openbay_dashboard'),
				'href'     => $this->url->link('marketplace/openbay', 'user_token=' . $this->session->data['user_token'], true),
				'children' => array()
			);

			$openbay_menu[] = array(
				'name'	   => $this->language->get('text_openbay_orders'),
				'href'     => $this->url->link('marketplace/openbay/orderlist', 'user_token=' . $this->session->data['user_token'], true),
				'children' => array()
			);

			$openbay_menu[] = array(
				'name'	   => $this->language->get('text_openbay_items'),
				'href'     => $this->url->link('marketplace/openbay/items', 'user_token=' . $this->session->data['user_token'], true),
				'children' => array()
			);

			// eBay sub menu
			$ebay = array();

			if ($this->user->hasPermission('access', 'extension/openbay/ebay') && $this->config->get('ebay_status') == 1) {
				$ebay[] = array(
					'name'     => $this->language->get('text_openbay_dashboard'),
					'href'     => $this->url->link('extension/openbay/ebay', 'user_token=' . $this->session->data['user_token'], true),
					'children' => array()
				);

				$ebay[] = array(
					'name'	   => $this->language->get('text_openbay_settings'),
					'href'     => $this->url->link('extension/openbay/ebay/settings', 'user_token=' . $this->session->data['user_token'], true),
					'children' => array()
				);

				$ebay[] = array(
					'name'	   => $this->language->get('text_openbay_links'),
					'href'     => $this->url->link('extension/openbay/ebay/viewitemlinks', 'user_token=' . $this->session->data['user_token'], true),
					'children' => array()
				);

				$ebay[] = array(
					'name'	   => $this->language->get('text_openbay_order_import'),
					'href'     => $this->url->link('extension/openbay/ebay/vieworderimport', 'user_token=' . $this->session->data['user_token'], true),
					'children' => array()
				);
			}

			if ($ebay) {
				$openbay_menu[] = array(
					'name'	   => $this->language->get('text_openbay_ebay'),
					'href'     => '',
					'children' => $ebay
				);
			}

			// Amazon EU sub menu
			$amazon_eu = array();

			if ($this->user->hasPermission('access', 'extension/openbay/amazon') && $this->config->get('openbay_amazon_status') == 1) {
				$amazon_eu[] = array(
					'name'     => $this->language->get('text_openbay_dashboard'),
					'href'     => $this->url->link('extension/openbay/amazon', 'user_token=' . $this->session->data['user_token'], true),
					'children' => array()
				);

				$amazon_eu[] = array(
					'name'	   => $this->language->get('text_openbay_settings'),
					'href'     => $this->url->link('extension/openbay/amazon/settings', 'user_token=' . $this->session->data['user_token'], true),
					'children' => array()
				);

				$amazon_eu[] = array(
					'name'	   => $this->language->get('text_openbay_links'),
					'href'     => $this->url->link('extension/openbay/amazon/itemlinks', 'user_token=' . $this->session->data['user_token'], true),
					'children' => array()
				);
			}

			if ($amazon_eu) {
				$openbay_menu[] = array(
					'name'	   => $this->language->get('text_openbay_amazon'),
					'href'     => '',
					'children' => $amazon_eu
				);
			}

			// Amazon US sub menu
			$amazon_us = array();

			if ($this->user->hasPermission('access', 'extension/openbay/amazonus') && $this->config->get('openbay_amazonus_status') == 1) {
				$amazon_us[] = array(
					'name'     => $this->language->get('text_openbay_dashboard'),
					'href'     => $this->url->link('extension/openbay/amazonus', 'user_token=' . $this->session->data['user_token'], true),
					'children' => array()
				);

				$amazon_us[] = array(
					'name'	   => $this->language->get('text_openbay_settings'),
					'href'     => $this->url->link('extension/openbay/amazonus/settings', 'user_token=' . $this->session->data['user_token'], true),
					'children' => array()
				);

				$amazon_us[] = array(
					'name'	   => $this->language->get('text_openbay_links'),
					'href'     => $this->url->link('extension/openbay/amazonus/itemlinks', 'user_token=' . $this->session->data['user_token'], true),
					'children' => array()
				);
			}

			if ($amazon_us) {
				$openbay_menu[] = array(
					'name'	   => $this->language->get('text_openbay_amazonus'),
					'href'     => '',
					'children' => $amazon_us
				);
			}

			// Etsy sub menu
			$etsy = array();

			if ($this->user->hasPermission('access', 'extension/openbay/etsy') && $this->config->get('etsy_status') == 1) {
				$etsy[] = array(
					'name'     => $this->language->get('text_openbay_dashboard'),
					'href'     => $this->url->link('extension/openbay/etsy', 'user_token=' . $this->session->data['user_token'], true),
					'children' => array()
				);

				$etsy[] = array(
					'name'	   => $this->language->get('text_openbay_settings'),
					'href'     => $this->url->link('extension/openbay/etsy/settings', 'user_token=' . $this->session->data['user_token'], true),
					'children' => array()
				);

				if ($this->user->hasPermission('access', 'extension/openbay/etsy_product')) {
					$etsy[] = array(
						'name'	   => $this->language->get('text_openbay_links'),
						'href'     => $this->url->link('extension/openbay/etsy_product/links', 'user_token=' . $this->session->data['user_token'], true),
						'children' => array()
					);
				}
			}

			if ($etsy) {
				$openbay_menu[] = array(
					'name'	   => $this->language->get('text_openbay_etsy'),
					'href'     => '',
					'children' => $etsy
				);
			}

			// FBA sub menu
			$fba = array();

			if ($this->user->hasPermission('access', 'extension/openbay/fba') && $this->config->get('openbay_fba_status') == 1) {
				$fba[] = array(
					'name'     => $this->language->get('text_openbay_dashboard'),
					'href'     => $this->url->link('extension/openbay/fba', 'user_token=' . $this->session->data['user_token'], true),
					'children' => array()
				);

				$fba[] = array(
					'name'	   => $this->language->get('text_openbay_settings'),
					'href'     => $this->url->link('extension/openbay/fba/settings', 'user_token=' . $this->session->data['user_token'], true),
					'children' => array()
				);

				$fba[] = array(
					'name'	   => $this->language->get('text_openbay_fulfillmentlist'),
					'href'     => $this->url->link('extension/openbay/fba/fulfillmentlist', 'user_token=' . $this->session->data['user_token'], true),
					'children' => array()
				);

				$fba[] = array(
					'name'	   => $this->language->get('text_openbay_orderlist'),
					'href'     => $this->url->link('extension/openbay/fba/orderlist', 'user_token=' . $this->session->data['user_token'], true),
					'children' => array()
				);
			}

			if ($fba) {
				$openbay_menu[] = array(
					'name'	   => $this->language->get('text_openbay_fba'),
					'href'     => '',
					'children' => $fba
				);
			}
		}

		if ($openbay_menu) {
			$data['menus'][] = array(
				'id'       => 'menu-openbay',
				'icon'	   => 'fa-cubes',
				'name'	   => $this->language->get('text_openbay_extension'),
				'href'     => '',
				'children' => $openbay_menu
			);
		}
	}

	public function purge() {
		/**
		 * This is a function that is very dangerous
		 * Only developers should use this if you need to!!
		 * You need this code: **135** (includes stars)
		 *
		 * ACTIONS HERE CANNOT BE UNDONE WITHOUT A BACKUP
		 *
		 * !! IMPORTANT !!
		 * This section will by default comment out the database delete actions
		 * If you want to use them, uncomment.
		 * When you are finished, ensure you comment them back out!
		 */

		$this->log->write('User is trying to wipe system data');

		if ($this->request->post['pass'] != '**135**') {
			$this->log->write('User failed password validation');
			$json = array('msg' => 'Password wrong, check the source code for the password! This is so you know what this feature does.');
		} else {
			/**
			$this->log->write('User passed validation');
			$this->db->query("TRUNCATE `" . DB_PREFIX . "order`");
			$this->db->query("TRUNCATE `" . DB_PREFIX . "order_history`");
			$this->db->query("TRUNCATE `" . DB_PREFIX . "order_option`");
			$this->db->query("TRUNCATE `" . DB_PREFIX . "order_product`");
			$this->db->query("TRUNCATE `" . DB_PREFIX . "order_total`");
			$this->db->query("TRUNCATE `" . DB_PREFIX . "customer`");
			$this->db->query("TRUNCATE `" . DB_PREFIX . "customer_activity`");
			$this->db->query("TRUNCATE `" . DB_PREFIX . "customer_ban_ip`");
			$this->db->query("TRUNCATE `" . DB_PREFIX . "customer_transaction`");
			$this->db->query("TRUNCATE `" . DB_PREFIX . "address`");

			$this->db->query("TRUNCATE `" . DB_PREFIX . "ebay_order`");
			$this->db->query("TRUNCATE `" . DB_PREFIX . "ebay_order_lock`");
			$this->db->query("TRUNCATE `" . DB_PREFIX . "ebay_transaction`");

			if ($this->config->get('ebay_status') == 1) {
			$this->db->query("TRUNCATE `" . DB_PREFIX . "ebay_category`");
			$this->db->query("TRUNCATE `" . DB_PREFIX . "ebay_category_history`");
			$this->db->query("TRUNCATE `" . DB_PREFIX . "ebay_image_import`");
			$this->db->query("TRUNCATE `" . DB_PREFIX . "ebay_listing`");
			$this->db->query("TRUNCATE `" . DB_PREFIX . "ebay_listing_pending`");
			$this->db->query("TRUNCATE `" . DB_PREFIX . "ebay_stock_reserve`");
			$this->db->query("TRUNCATE `" . DB_PREFIX . "ebay_payment_method`");
			$this->db->query("TRUNCATE `" . DB_PREFIX . "ebay_profile`");
			$this->db->query("TRUNCATE `" . DB_PREFIX . "ebay_setting_option`");
			$this->db->query("TRUNCATE `" . DB_PREFIX . "ebay_shipping`");
			$this->db->query("TRUNCATE `" . DB_PREFIX . "ebay_shipping_location`");
			$this->db->query("TRUNCATE `" . DB_PREFIX . "ebay_shipping_location_exclude`");
			$this->db->query("TRUNCATE `" . DB_PREFIX . "ebay_template`");
			}

			if ($this->config->get('etsy_status') == 1) {
			$this->db->query("TRUNCATE `" . DB_PREFIX . "etsy_listing`");
			$this->db->query("TRUNCATE `" . DB_PREFIX . "etsy_setting_option`");
			}

			$this->db->query("TRUNCATE `" . DB_PREFIX . "etsy_order`");
			$this->db->query("TRUNCATE `" . DB_PREFIX . "etsy_order_lock`");
			$this->db->query("TRUNCATE `" . DB_PREFIX . "manufacturer`");
			$this->db->query("TRUNCATE `" . DB_PREFIX . "manufacturer_to_store`");
			$this->db->query("TRUNCATE `" . DB_PREFIX . "attribute`");
			$this->db->query("TRUNCATE `" . DB_PREFIX . "attribute_description`");
			$this->db->query("TRUNCATE `" . DB_PREFIX . "attribute_group`");
			$this->db->query("TRUNCATE `" . DB_PREFIX . "attribute_group_description`");
			$this->db->query("TRUNCATE `" . DB_PREFIX . "ebay_listing`");
			$this->db->query("TRUNCATE `" . DB_PREFIX . "category`");
			$this->db->query("TRUNCATE `" . DB_PREFIX . "category_description`");
			$this->db->query("TRUNCATE `" . DB_PREFIX . "category_to_store`");
			$this->db->query("TRUNCATE `" . DB_PREFIX . "product`");
			$this->db->query("TRUNCATE `" . DB_PREFIX . "product_to_store`");
			$this->db->query("TRUNCATE `" . DB_PREFIX . "product_description`");
			$this->db->query("TRUNCATE `" . DB_PREFIX . "product_attribute`");
			$this->db->query("TRUNCATE `" . DB_PREFIX . "product_option`");
			$this->db->query("TRUNCATE `" . DB_PREFIX . "product_option_value`");
			$this->db->query("TRUNCATE `" . DB_PREFIX . "product_image`");
			$this->db->query("TRUNCATE `" . DB_PREFIX . "product_to_category`");
			$this->db->query("TRUNCATE `" . DB_PREFIX . "option`");
			$this->db->query("TRUNCATE `" . DB_PREFIX . "option_description`");
			$this->db->query("TRUNCATE `" . DB_PREFIX . "option_value`");
			$this->db->query("TRUNCATE `" . DB_PREFIX . "option_value_description`");

			if ($this->openbay->addonLoad('openstock')) {
			$this->db->query("TRUNCATE `" . DB_PREFIX . "product_option_relation`");
			}
			 */

			$this->log->write('Data cleared');
			$json = array('msg' => 'Data cleared');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
