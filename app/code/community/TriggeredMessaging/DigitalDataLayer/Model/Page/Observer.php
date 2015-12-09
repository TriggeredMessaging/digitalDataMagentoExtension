<?php

class TriggeredMessaging_DigitalDataLayer_Model_Page_Observer
{

    // Specification is at
    // http://www.w3.org/2013/12/ceddl-201312.pdf
    protected $_version = "1.0";
    protected $_user = null;
    protected $_page = null;
    protected $_cart = null;
    protected $_product = null;
    protected $_search = null;
    protected $_transaction = null;
    protected $_listing = null;
    protected $_events = array();

    protected $_debug = false;

    protected function _getRequest()
    {
        return Mage::app()->getFrontController()->getRequest();
    }

    /*
     * Returns Controller Name
     */
    protected function _getControllerName()
    {
        return $this->_getRequest()->getControllerName();
    }

    protected function _getActionName()
    {
        return $this->_getRequest()->getActionName();
    }

    protected function _getModuleName()
    {
        return $this->_getRequest()->getModuleName();
    }

    protected function _getRouteName()
    {
        return $this->_getRequest()->getRouteName();
    }

    protected function _getCustomer()
    {
        return Mage::helper('customer')->getCustomer();
    }

    protected function _getBreadcrumb()
    {
        return Mage::helper('catalog')->getBreadcrumbPath();
    }

    protected function _getCategory($category_id)
    {
        return Mage::getModel('catalog/category')->load($category_id);
    }

    protected function _getCurrentProduct()
    {
        return Mage::registry('current_product');
    }

    protected function _getProduct($productId)
    {
        return Mage::getModel('catalog/product')->load($productId);
    }

    protected function _getCurrentCategory()
    {
        return Mage::registry('current_category');
    }

    protected function _getCatalogSearch()
    {
        return Mage::getSingleton('catalogsearch/advanced');
    }

    protected function _getCheckoutSession()
    {
        return Mage::getSingleton('checkout/session');
    }

    protected function _getSalesOrder()
    {
        return Mage::getModel('sales/order');
    }

    protected function _getOrderAddress()
    {
        return Mage::getModel('sales/order_address');
    }

    /*
     * Determine which page type we're on
     */

    public function _isHome()
    {
        if (Mage::app()->getRequest()->getRequestString() == "/") {
            return true;
        } else {
            return false;
        }
    }

    public function _isContent()
    {
        if ($this->_getModuleName() == 'cms') {
            return true;
        } else {
            return false;
        }
    }

    public function _isCategory()
    {
        if ($this->_getControllerName() == 'category') {
            return true;
        } else {
            return false;
        }
    }

    public function _isSearch()
    {
        if ($this->_getModuleName() == 'catalogsearch') {
            return true;
        } else {
            return false;
        }
    }

    public function _isProduct()
    {
        $onCatalog = false;
        if (Mage::registry('current_product')) {
            $onCatalog = true;
        }
        return $onCatalog;
    }

    public function _isCart()
    {
        try {
            $request = $this->_getRequest();
            $module = $request->getModuleName();
            $controller = $request->getControllerName();
            $action = $request->getActionName();
            if ($module == 'checkout' && $controller == 'cart' && $action == 'index') {
                return true;
            }
        } catch (Exception $e) {
        }

        return false;
    }

    public function _isCheckout()
    {
        if (strpos($this->_getModuleName(), 'checkout') !== false && $this->_getActionName() != 'success') {
            return true;
        } else {
            return false;
        }
    }

    public function _isConfirmation()
    {
        // default controllerName is "onepage"
        // relax the check, only check if it contains checkout
        // some checkout systems have different prefix/postfix,
        // but all contain checkout
        if (strpos($this->_getModuleName(), 'checkout') !== false && $this->_getActionName() == "success") {
            return true;
        } else {
            return false;
        }
    }


    /*
     * Get information on pages to pass to front end
     */
    public function getCurrentPrice($_price, $p1, $p2)
    {
        if ($p1) {
            return number_format(Mage::helper('core')->currency((float)$_price, $p1, $p2), 2, '.', '');
        } else {
            return floatval(number_format(Mage::helper('core')->currency((float)$_price, $p1, $p2), 2, '.', ''));
        }
    }
	
	/*
     * Get information on pages to pass to front end
     */
    public function getProductPrice($_product, $_price, $IncTax)
    {
		$tax_helper = Mage::helper('tax');
        return round(Mage::helper('core')->currency($tax_helper->getPrice($_product,$_price, $IncTax),false,false),2);
    }
	
    public function getVersion()
    {
        return $this->_version;
    }

    public function getPurchaseCompleteQs()
    {

        $orderId = $this->_getCheckoutSession()->getLastOrderId();
        if ($orderId) {
            $order = $this->_getSalesOrder()->load($orderId);
            $email = $order->getCustomerEmail();
        } else {
            $email = $user->getEmail();
        }
        $qs = "e=" . urlencode($email);

        if ($orderId) {
            $qs = $qs . "&r=" . urlencode($orderId);
        }

        return $qs;
    }

    public function getUser()
    {
        return $this->_user;
    }

    public function getPage()
    {
        return $this->_page;
    }

    public function getProduct()
    {
        return $this->_product;
    }

    public function getCart()
    {
        return $this->_cart;
    }

    public function getTransaction()
    {
        return $this->_transaction;
    }

    public function getListing()
    {
        return $this->_listing;
    }

    public function getEvents()
    {
        return array();
    }


    public function getCategoryListingCollection()
    {
        $limit = Mage::helper('digital_data_layer_main')->getConfigProductListLimit();
        if( 0 === $limit ){
            return null;
        }

        // original code - getting the collection form the block instance - deprecated function
        // Grab product list data (from category and search pages)
        //$listingCollection = Mage::getBlockSingleton('catalog/product_list')->getLoadedProductCollection();


        // new code - building custom collections
        $currentCategory = Mage::registry('current_category');
        // CATEGORY PAGE
        if($currentCategory ){
            $collection = $currentCategory->getProductCollection();

        // QUICK SEARCH PAGE
        } elseif($this->_isSearch() && Mage::helper('catalogsearch')->getQueryText()) {

            $searchText = Mage::helper('catalogsearch')->getQueryText();
            $query = Mage::getModel('catalogsearch/query')->setQueryText($searchText)->prepare();
            $fulltextResource = Mage::getResourceModel('catalogsearch/fulltext')->prepareResult(
                Mage::getModel('catalogsearch/fulltext'),
                $searchText,
                $query
            );

            $collection = Mage::getResourceModel('catalog/product_collection');
            $collection->getSelect()->joinInner(
                array('search_result' => $collection->getTable('catalogsearch/result')),
                $collection->getConnection()->quoteInto(
                    'search_result.product_id=e.entity_id AND search_result.query_id=?',
                    $query->getId()
                ),
                array('relevance' => 'relevance')
            );

        // ADVANCED SEARCH collection - already loaded, need to clone/clear it first
        } elseif($this->_isSearch() && Mage::getSingleton('catalogsearch/advanced')->getProductCollection()->getSize()) {
            $collection = clone Mage::getSingleton('catalogsearch/advanced')->getProductCollection();
            $collection->clear();

        // EXIT if none of the above
        } else {

                return null;
        }


        // add extra features/filters to collection
        $collection
            ->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes()) // Product Listing attributes
            // ->addAttributeToSelect('*') // add all attributes - optional
            ->addAttributeToFilter('status', 1) // enabled only
            ->addAttributeToSelect('image') // main image
            // ->addAttributeToFilter('visibility', 4) //visibility in catalog,search
            ->setVisibility(Mage::getSingleton('catalog/product_visibility')->getVisibleInCatalogIds())
            ->addStoreFilter()
            ->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            // ->setOrder('price', 'ASC') //sets the order by price
        ;
        if($currentCategory){
            $collection->addUrlRewrite($currentCategory->getId());
        }
        // add stock filter
        Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($collection);
        // add limits
        if( -1 !== $limit ){
            $collection->setPageSize($limit)->setCurPage(1);
        }

        return $collection;
    }

    public function getCategoryProductsToShow()
    {
        $output = array();
        if($productListing = $this->getCategoryListingCollection()){
            foreach($productListing as $item){
                $output[] = $this->_getProductModel($item, 'list');
            }
        }

        return $output;
    }




    /*
     * Set the model attributes to be passed front end
     */

    public function _getPageType()
    {
        try {
            if ($this->_isHome()) {
                return 'home';
            } elseif ($this->_isContent()) {
                return 'content';
            } elseif ($this->_isCategory()) {
                return 'category';
            } elseif ($this->_isSearch()) {
                return 'search';
            } elseif ($this->_isProduct()) {
                return 'product';
            } elseif ($this->_isCart()) {
                return 'basket';
            } elseif ($this->_isCheckout()) {
                return 'checkout';
            } elseif ($this->_isConfirmation()) {
                return 'confirmation';
            } else {
                return $this->_getModuleName();
            }
        } catch (Exception $e) {
        }
    }

    public function _getPageBreadcrumb()
    {
        $arr = $this->_getBreadcrumb();
        $breadcrumb = array();

        try {
            foreach ($arr as $category) {
                $breadcrumb[] = $category['label'];
            }
        } catch (Exception $e) {
        }

        return $breadcrumb;
    }

    public function _setPage()
    {
        /*
          Section 6.3 of http://www.w3.org/2013/12/ceddl-201312.pdf
          page: {
              pageInfo: {
                  pageID: "Great Winter Run 2015",
                  destinationURL: "http://www.greatrun.org/Events/Event.aspx?id=2"},
              category: {
                  primaryCategory: "Cameras",
                  subCategory1: "Nikon",
                  pageType: "product"
              }
         */

        try {
            $this->_page = array();

            $this->_page['pageInfo'] = array();
            // $this->_page['pageInfo']['pageID']
            $this->_page['pageInfo']['pageName'] = '';
            $this->_page['pageInfo']['destinationURL'] = Mage::helper('core/url')->getCurrentUrl();
            $referringURL = Mage::app()->getRequest()->getServer('HTTP_REFERER');
            if ($referringURL) {
                $this->_page['pageInfo']['referringURL'] = $referringURL;
            }
            // $this->_page['pageInfo']['sysEnv']
            // $this->_page['pageInfo']['variant']
            if ($this->_getPageBreadcrumb()) {
                $this->_page['pageInfo']['breadcrumbs'] = $this->_getPageBreadcrumb();
            }
            // $this->_page['pageInfo']['author']
            // $this->_page['pageInfo']['issueDate']
            // $this->_page['pageInfo']['effectiveDate']
            // $this->_page['pageInfo']['expiryDate']
            $this->_page['pageInfo']['language'] = Mage::app()->getLocale()->getLocaleCode();
            // $this->_page['pageInfo']['geoRegion']
            // $this->_page['pageInfo']['industryCodes']
            // $this->_page['pageInfo']['publisher']

            $this->_page['category'] = array();
            if (Mage::registry('current_category')) {
                // There must be a better way than this
                $this->_page['category']['primaryCategory'] = Mage::registry('current_category')->getName();
            }
            // $this->_page['category']['subCategory1'];
            $this->_page['category']['pageType'] = $this->_getPageType();

            // $this->_page['attributes'] = array();

            if ($this->_debug) {
                $modules = (array)Mage::getConfig()->getNode('modules')->children();
                $mods = array();
                foreach ($modules as $key => $value) {
                    if (strpos($key, 'Mage_') === false) {
                        $mods[] = $key;

                    }
                }
                $this->_page['extra_modules'] = $mods;
            }

        } catch (Exception $e) {
        }
    }

    // Set the user info
    public function _setUser()
    {
        // Section 6.9 of http://www.w3.org/2013/12/ceddl-201312.pdf

        try {
            $this->_user = array();
            $user = $this->_getCustomer();
            $user_id = $user->getEntityId();
            $firstName = $user->getFirstname();
            $lastName = $user->getLastname();
            $userGroup = Mage::getModel('customer/group')->load(Mage::getSingleton('customer/session')->getCustomerGroupId());

            if ($this->_isConfirmation()) {
                $orderId = $this->_getCheckoutSession()->getLastOrderId();
                if ($orderId) {
                    $order = $this->_getSalesOrder()->load($orderId);
                    $email = $order->getCustomerEmail();
                }
            } else {
                $email = $user->getEmail();
            }

            $this->_user['profile'] = array();

            $profile = array();

            $profile['profileInfo'] = array();
            if ($user_id) {
                $profile['profileInfo']['profileID'] = (string)$user_id;
            }
            if ($firstName) {
                $profile['profileInfo']['userFirstName'] = $firstName;
            }
            if ($lastName) {
                $profile['profileInfo']['userLastName'] = $lastName;
            }
            if ($email) {
                $profile['profileInfo']['email'] = $email;
            }
            $profile['profileInfo']['language'] = Mage::getStoreConfig('general/locale/code', Mage::app()->getStore()->getId());
            $profile['profileInfo']['returningStatus'] = $user_id ? 'true' : 'false';
            if ($userGroup && $this->_userGroupExp) {
                $profile['profileInfo']['segment']['userGroupId'] = $userGroup->getData('customer_group_id');
                $profile['profileInfo']['segment']['userGroup'] = $userGroup->getData('customer_group_code');
            }

            // $profile['address'] = array();
            // $profile['address']['line1'];
            // $profile['address']['line2'];
            // $profile['address']['city'];
            // $profile['address']['stateProvince'];
            // $profile['address']['postalCode'];
            // $profile['address']['country'];

            // $profile['social'] = array();
            // $profile['attributes'] = array();

            array_push($this->_user['profile'], $profile);
        } catch (Exception $e) {
        }
    }

    public function _getAddress($address)
    {
        /*
          address: {
            line1: "",
            line2: "",
            city: "",
            stateProvince: "",
            postalCode: "",
            country: ""
          },
         */

        $billing = array();

        try {
            if ($address) {
                $billing['line1'] = $address->getName();
                $billing['line2'] = $address->getStreetFull();
                $billing['city'] = $address->getCity();
                $billing['postalCode'] = $address->getPostcode();
                $billing['country'] = $address->getCountry();
                $state = $address->getRegion();
                $billing['stateProvince'] = $state ? $state : '';
            }
        } catch (Exception $e) {
        }

        return $billing;
    }

    public function _getProductStock($product)
    {
        return (int)Mage::getModel('cataloginventory/stock_item')->loadByProduct($product)->getQty();
    }

    public function _getCurrency()
    {
        return Mage::app()->getStore()->getCurrentCurrencyCode();
    }

    public function _getProductModel($product, $_page_)
    {
        /*
          Section 6.4 of http://www.w3.org/2013/12/ceddl-201312.pdf
          product: [
              {
                  productInfo: {
                      productID: "greatwinterrun2015", // Unique product ID
                      productName: "Great Winter Run",
                      description: "Running, in the winter, in Edinburgh",
                      productURL: "http://www.greatrun.org/Events/Event.aspx?id=2",
                      productImage: "http://www.greatrun.org/Events/App_Images/slideshow/cropped/saved/gwir_01f0a780d94.jpg",
                      productThumbnail: "http://www.greatrun.org/App_Images/2011/Events/logo_GWIR.jpg"
                  },
                  category: {
                      primaryCategory: "Somecategory",
                      subCategory1: "Subcat"
                  },
                  attributes: {
                      distance: 5000,
                      country: "Scotland",
                      date: "2015-01-11", // Dates in ISO 8601
                      city: "Edinburgh" // You can put any extended data you want passing through in attributes
                  },
                  price: {
                      basePrice: 40.00,
                      currency: "GBP",
                      taxRate: 0.2,
                      priceWithTax: 48.00
                  }
              }
          ]
         */

        $product_model = array();
        $options = array();
        //If there is optional data then add it
        if ($_page_ === 'cart') {
            $opt = $product->getProduct()->getTypeInstance(true)->getOrderOptions($product->getProduct());
            if (isset($opt['attributes_info'])) {
                foreach ($opt['attributes_info'] as $attribute) {
                    $options[$attribute['label']] = $attribute['value'];
                }
            }
            $productId = $product->getProductId();
            $product = $this->_getProduct($productId);
        }
        if ($_page_ === 'list') {
            // optimisation - do not re-load the whole product, fetch all needed attributes into the category collection
            if (Mage::helper('digital_data_layer_main')->shouldReloadProductOnCategoryListing()){
                $productId = $product->getId();
                $product = $this->_getProduct($productId);
            }
        }
        try {
            // Product Info
            $product_model['productInfo'] = array();
            $product_model['productInfo']['productID'] = $product->getId();
            $product_model['productInfo']['sku'] = $product->getSku();
            $product_model['productInfo']['productName'] = $product->getName();
            $product_model['productInfo']['description'] = strip_tags($product->getShortDescription());
            $product_model['productInfo']['productURL'] = $product->getProductUrl();
			$stock = $this->_getProductStock($product);
			if($this->_stockExp==2){
				$product_model['productInfo']['stock'] = $stock;
			} else if($this->_stockExp==1){
				if($stock>0){
					$product_model['productInfo']['stock'] = 'In Stock';
				} else {
					$product_model['productInfo']['stock'] = 'Out of Stock';
				}
			}

            //Check if images contain placeholders
            if ($product->getImage() && $product->getImage() !== "no_selection") {
                $product_model['productInfo']['productImage'] = $product->getImageUrl();
            }
            if ($product->getThumbnail() && $product->getThumbnail() !== "no_selection") {
                $product_model['productInfo']['productThumbnail'] = $product->getThumbnailUrl();
            }
            //Attributes
            if ($product->getWeight() && in_array('weight', $this->_expAttr)) {
                $product_model['attributes']['weight'] = floatval($product->getWeight());
            }
            try {
                $attributes = Mage::getSingleton('eav/config')->getEntityType(Mage_Catalog_Model_Product::ENTITY)->getAttributeCollection();
                foreach ($attributes as $attr) {
                    $attrCode = $attr->getAttributecode();
                    if ($attrCode === 'color' || $attrCode === 'manufacturer' || $attrCode === 'size') {
                        if ($product->getAttributeText($attrCode) && in_array($attrCode, $this->_expAttr)) {
                            $product_model['productInfo'][$attrCode] = $product->getAttributeText($attrCode);
                        }
                    } elseif ($attr->getData('is_user_defined') && in_array($attrCode, $this->_expAttr) && $product->getData($attrCode)) {
                        if ($attr->getData('frontend_class') === 'validate-number') {
                            if ($attr->getFrontend()->getValue($product) !== 'No') {
                                $product_model['attributes'][$attrCode] = floatval($attr->getFrontend()->getValue($product));
                            }
                        } elseif ($attr->getData('frontend_class') === 'validate-digits') {
                            if ($attr->getFrontend()->getValue($product) !== 'No') {
                                $product_model['attributes'][$attrCode] = intval($attr->getFrontend()->getValue($product));
                            }
                        } else {
                            if ($product->getAttributeText($attrCode)){
                                $product_model['attributes'][$attrCode] = $product->getAttributeText($attrCode);
                            } elseif ($product->getData($attrCode)) {
                                $product_model['attributes'][$attrCode] = $product->getData($attrCode);
                            }
                        }
                    }
                }
                //Add the options captured earlier
                if (count($options)) {
                    $product_model['attributes']['options'] = $options;
                }
            } catch (Exception $e) {
            }
            // Category
            // Iterates through all categories, checking for duplicates
            $allcategories = $this->_getProductCategories($product);
            if ($allcategories) {
                $catiterator = 0;
                $setCategories = array();
                foreach ($allcategories as $cat) {
                    if ($catiterator === 0) {
                        $product_model['category']['primaryCategory'] = $cat;
                        $catiterator++;

                    } else {
                        if (!in_array($cat, $setCategories)) {
                            $product_model['category']["subCategory$catiterator"] = $cat;
                            $catiterator++;
                        }
                    }
                    array_push($setCategories, $cat);
                }
                if ($product->getTypeID()) {
                    $product_model['category']['productType'] = $product->getTypeID();
                }
            }

            // Price
            $product_model['price'] = array();
            if (!$this->getProductPrice($product,$product->getSpecialPrice(),false) || $this->getProductPrice($product,$product->getSpecialPrice(),false) !== $this->getProductPrice($product,$product->getFinalPrice(),false)) {
                $product_model['price']['basePrice'] = $this->getProductPrice($product,$product->getPrice(),false);
				$product_model['price']['priceWithTax'] = $this->getProductPrice($product,$product->getPrice(),true);
            } else {
                $product_model['price']['basePrice'] = $this->getProductPrice($product,$product->getSpecialPrice(),false);
				$product_model['price']['priceWithTax'] = $this->getProductPrice($product,$product->getSpecialPrice(),true);
                $product_model['price']['regularPrice'] = $this->getProductPrice($product,$product->getPrice(),false);
				$product_model['price']['regularPriceWithTax'] = $this->getProductPrice($product,$product->getPrice(),true);
            }
            $product_model['price']['currency'] = $this->_getCurrency();

            if (!$product_model['price']['priceWithTax']) {
                unset($product_model['price']['priceWithTax']);
            }

            // In case 'basePrice' did not exist
            if (!$product_model['price']['basePrice']) {
                $product_model['price']['basePrice'] = $this->getProductPrice($product,$product->getGroupPrice(), false);
            }
            if (!$product_model['price']['basePrice']) {
                $product_model['price']['basePrice'] = $this->getProductPrice($product,$product->getMinimalPrice(), false);
            }
            if (!$product_model['price']['basePrice']) {
                $product_model['price']['basePrice'] = $this->getProductPrice($product,$product->getSpecialPrice(), false);
            }
            if (!$product_model['price']['basePrice']) {
                // Extract price for bundle products
                $price_model = $product->getPriceModel();
                if (method_exists($price_model, 'getOptions')) {
                    $normal_price = 0.0;
                    $_options = $price_model->getOptions($product);
                    foreach ($_options as $_option) {
                        if (!method_exists($_option, 'getDefaultSelection')) {
                            break;
                        }
                        $_selection = $_option->getDefaultSelection();
                        if ($_selection === null) continue;
                        $normal_price += $this->getProductPrice($product,$_selection->getPrice(), false);
                    }
                    $product_model['price']['basePrice'] = $normal_price;
                }
            }

            if ($this->_debug) {
                $product_model['price']['all'] = array();
                $product_model['price']['all']['getPrice'] = $this->getProductPrice($product,$product->getPrice(), false);
                $product_model['price']['all']['getMinimalPrice'] = $this->getProductPrice($product,$product->getMinimalPrice(), false);
                $product_model['price']['all']['getPriceModel'] = $product->getPriceModel();
                $product_model['price']['all']['getGroupPrice'] = $this->getProductPrice($product,$product->getGroupPrice(), false);
                $product_model['price']['all']['getTierPrice'] = $product->getTierPrice();
                $product_model['price']['all']['getTierPriceCount'] = $product->getTierPriceCount();
                $product_model['price']['all']['getFormatedTierPrice'] = $product->getFormatedTierPrice();
                $product_model['price']['all']['getFormatedPrice'] = $product->getFormatedPrice();
                $product_model['price']['all']['getFinalPrice'] = $this->getProductPrice($product,$product->getFinalPrice(), false);
                $product_model['price']['all']['getCalculatedFinalPrice'] = $product->getCalculatedFinalPrice();
                $product_model['price']['all']['getSpecialPrice'] = $this->getProductPrice($product,$product->getSpecialPrice(), false);
            }

            // Calculate Tax Rate
            $store = Mage::app()->getStore('default');
            $taxCalculation = Mage::getModel('tax/calculation');
            $request = $taxCalculation->getRateRequest(null, null, null, $store);
            $taxClassId = $product->getTaxClassId();
            $percent = $taxCalculation->getRate($request->setProductClassId($taxClassId));
            $product_model['price']['taxRate'] = ((float)$percent) / 100;

            // For configurable/grouped/composite products, add all associated products to 'linkedProduct'
            if ($_page_ === 'product') {
                if ($product->isConfigurable() || $product->isGrouped() || $product->isComposite()) {

                    $product_model['linkedProduct'] = array();
                    $simple_collection = array();

                    // Add simple products related to configurable products
                    if ($product->isConfigurable()) {
                        $conf = Mage::getModel('catalog/product_type_configurable')->setProduct($product);
                        $simple_collection = $conf->getUsedProductCollection()->addAttributeToSelect('*')->addFilterByRequiredOptions();
                    } else {
                        $type_instance = $product->getTypeInstance(true);
                        if (method_exists($type_instance, 'getSelectionsCollection')) {
                            // Add simple products related to bundle products
                            $simple_collection = $type_instance->getSelectionsCollection(
                                $type_instance->getOptionsIds($product), $product
                            );
                        } else if (method_exists($type_instance, 'getAssociatedProducts')) {
                            // Add simple products related to grouped products
                            $simple_collection = $type_instance->getAssociatedProducts($product);
                        }
                    }

                    // Add related products to the data layer
                    $min_price = 0.0;
                    foreach ($simple_collection as $simple_product) {
                        array_push($product_model['linkedProduct'], $this->_getProductModel($simple_product, false));
                        $simple_product_price = $this->getCurrentPrice(floatval($simple_product->getPrice()), false, false);
                        if ($simple_product_price && (!$min_price || $simple_product_price < $min_price)) {
                            $min_price = $simple_product_price;
                        }
                    }

                    // If price could not be extracted before, can set it now
                    if (!$product_model['price']['basePrice']) {
                        $product_model['price']['basePrice'] = $this->getCurrentPrice(floatval($min_price), false, false);
                    }

                    if (!$product_model['linkedProduct']) {
                        unset($product_model['linkedProduct']);
                    }
                }
            }

            if ($this->_debug) {
                $product_model['more']['isConfigurable'] = $product->isConfigurable();
                $product_model['more']['isSuperGroup'] = $product->isSuperGroup();
                $product_model['more']['isSuperConfig'] = $product->isSuperConfig();
                $product_model['more']['isGrouped'] = $product->isGrouped();
                $product_model['more']['isSuper'] = $product->isSuper();
                $product_model['more']['isVirtual'] = $product->isVirtual();
                $product_model['more']['isRecurring'] = $product->isRecurring();
                $product_model['more']['isComposite'] = $product->isComposite();
                $product_model['more']['getTypeId'] = $product->getTypeId();
                $product_model['more']['getImage'] = $product->getImage();
                $product_model['more']['getImageURL'] = $product->getImageUrl();
                $product_model['more']['getSmallImage'] = $product->getSmallImage();
                $product_model['more']['getSmallImageURL'] = $product->getSmallImageUrl();
                $product_model['more']['getThumbnail'] = $product->getThumbnail();
                $product_model['more']['getThumbnailURL'] = $product->getThumbnailUrl();
            }
        } catch (Exception $e) {
        }

        return $product_model;
    }

    public function _getProductCategories($product)
    {
        try {
            $cats = $product->getCategoryIds();
            if ($cats) {
                $category_names = array();
                foreach ($cats as $category_id) {
                    $_cat = $this->_getCategory($category_id);
                    $category_names[] = $_cat->getName();
                }
                return $category_names;
            }
        } catch (Exception $e) {
        }

        return false;
    }

    public function _getLineItems($items, $page_type)
    {
        /*
          item: [
              {
                  productInfo: {
                      productID: "greatwinterrun2015", // Unique product ID - links the prod in the cart to the one browsed
                      // If data isn't available on the cart page, as long as the productID is present, it will be filled
                      // out from our product database (e.g. name, images, category, extra attributes, price)
                      productName: "Great Winter Run",
                      description: "Running, in the winter, in Edinburgh",
                      productURL: "http://www.greatrun.org/Events/Event.aspx?id=2",
                      productImage: "http://www.greatrun.org/Events/App_Images/slideshow/cropped/saved/gwir_01f0a780d94.jpg",
                      productThumbnail: "http://www.greatrun.org/App_Images/2011/Events/logo_GWIR.jpg"
                  },
                  category: {
                      primaryCategory: "Somecategory",
                      subCategory1: "Subcat"
                  },
                  attributes: {
                      distance: 5000,
                      country: "Scotland",
                      date: "2015-01-11", // Dates in ISO 8601
                      city: "Edinburgh" // You can put any extended data you want passing through in attributes
                  },
                  price: {
                      basePrice: 40.00,
                      currency: "GBP",
                      taxRate: 0.2,
                      priceWithTax: 48.00
                  }
              }
          ]
         */

        $line_items = array();
        try {
            foreach ($items as $item) {
                $productId = $item->getProductId();
                $product = $this->_getProduct($productId);

                $parentIds = Mage::getModel('catalog/product_type_grouped')->getParentIdsByChild($productId);
                if (!empty($parentIds)) {
                    $litem_model = $this->_getProductModel($product, 'linked');
                    $litem_model['linkedProduct'] = array();
                    array_push($litem_model['linkedProduct'], $this->_getProductModel($this->_getProduct($parentIds[0]), 'linked'));
                } else {
                    $litem_model = $this->_getProductModel($item, 'cart');
                }
                if ($page_type === 'cart') {
                    $litem_model['quantity'] = floatval($item->getQty());
                } else {
                    $litem_model['quantity'] = floatval($item->getQtyOrdered());
                }

                if (!is_array($litem_model['price'])) {
                    $litem_model['price'] = array();
                }
                if ($item->getCouponCode()) {
                    $litem_model['price']['voucherCode'] = $item->getCouponCode();
                }
                if ($item->getDiscountAmount()) {
                    $litem_model['price']['voucherDiscount'] = abs(floatval($item->getDiscountAmount()));
                }
                // $litem_model['price']['shipping'];
                // $litem_model['price']['shippingMethod'] = $this->_extractShippingMethod($item->getQuote());
                //$litem_model['price']['priceWithTax'] = $this->getCurrentPrice(floatval($item->getBasePriceInclTax()), false, false); No longer needed with better tax calculation
                $litem_model['price']['cartTotal'] = floatval($item->getRowTotalInclTax()); //cart Total still useful for products that might have add-ons etc.

                if ($this->_debug) {
                    $litem_model['price']['all']['_getCalculationPrice'] = $this->getCurrentPrice($product->getCalculationPrice(), false, false);
                    $litem_model['price']['all']['_getCalculationPriceOriginal'] = $this->getCurrentPrice($product->getCalculationPriceOriginal(), false, false);
                    $litem_model['price']['all']['_getBaseCalculationPrice'] = $this->getCurrentPrice($product->getBaseCalculationPrice(), false, false);
                    $litem_model['price']['all']['_getBaseCalculationPriceOriginal'] = $this->getCurrentPrice($product->getBaseCalculationPriceOriginal(), false, false);
                    $litem_model['price']['all']['_getOriginalPrice'] = $this->getCurrentPrice($product->getOriginalPrice(), false, false);
                    $litem_model['price']['all']['_getBaseOriginalPrice'] = $this->getCurrentPrice($product->getBaseOriginalPrice(), false, false);
                    $litem_model['price']['all']['_getConvertedPrice'] = $this->getCurrentPrice($product->getConvertedPrice(), false, false);
                }

                // $litem_model['attributes'] = array();

                array_push($line_items, $litem_model);
            }
        } catch (Exception $e) {
        }

        return $line_items;
    }

    public function _setListing()
    {
        try {
            $this->_listing = array();
            if ($this->_isCategory()) {
                $category = $this->_getCurrentCategory();
            } elseif ($this->_isSearch()) {
                $category = $this->_getCatalogSearch();
                if (isset($_GET['q'])) {
                    $this->_listing['query'] = $_GET['q'];
                }
            }

            // Note: data on products are retrieved later, after the content layout block,
            // since the product list is compiled then.
        } catch (Exception $e) {
        }
    }

    public function _setProduct()
    {
        try {
            $product = $this->_getCurrentProduct();
            if (!$product) return false;
            $this->_product = array();
            array_push($this->_product, $this->_getProductModel($product, 'product'));
        } catch (Exception $e) {
        }
    }

    public function _setCart()
    {
        /*
          Section 6.5 of http://www.w3.org/2013/12/ceddl-201312.pdf
          cart: {
              price: {
                  basePrice: 40.00,
                  currency: "GBP",
                  taxRate: 0.2,
                  cartTotal: 48.00
              },
              item: [
                  {
                      productInfo: {
                          productID: "greatwinterrun2015", // Unique product ID - links the prod in the cart to the one browsed
                          // If data isn't available on the cart page, as long as the productID is present, it will be filled
                          // out from our product database (e.g. name, images, category, extra attributes, price)
                          productName: "Great Winter Run",
                          description: "Running, in the winter, in Edinburgh",
                          productURL: "http://www.greatrun.org/Events/Event.aspx?id=2",
                          productImage: "http://www.greatrun.org/Events/App_Images/slideshow/cropped/saved/gwir_01f0a780d94.jpg",
                          productThumbnail: "http://www.greatrun.org/App_Images/2011/Events/logo_GWIR.jpg"
                      },
                      category: {
                          primaryCategory: "Somecategory",
                          subCategory1: "Subcat"
                      },
                      attributes: {
                          distance: 5000,
                          country: "Scotland",
                          date: "2015-01-11", // Dates in ISO 8601
                          city: "Edinburgh" // You can put any extended data you want passing through in attributes
                      },
                      price: {
                          basePrice: 40.00,
                          currency: "GBP",
                          taxRate: 0.2,
                          priceWithTax: 48.00
                      }
                  }
              ]
          }
         */

        try {
            $basket = $this->_getCheckoutSession();

            if (!isset($basket)) {
                return;
            }

            $cart = array();
            $quote = $basket->getQuote();
            // Set normal params
            $cart_id = $basket->getQuoteId();
            if ($cart_id) {
                $cart['cartID'] = (string)$cart_id;
            }
            $cart['price'] = array();
	    if($quote->getSubtotal()){
		$cart['price']['basePrice'] = $quote->getSubtotal();
	    }
            if ($quote->getBaseSubtotal()) {
                $cart['price']['basePrice'] = $this->getCurrentPrice(floatval($quote->getBaseSubtotal()), false, false);
            } else {
                $cart['price']['basePrice'] = 0.0;
            }
            if ($quote->getShippingAddress()->getCouponCode()) {
                $cart['price']['voucherCode'] = $quote->getShippingAddress()->getCouponCode();
            }
            if ($quote->getShippingAddress()->getDiscountAmount()) {
                $cart['price']['voucherDiscount'] = abs((float)$quote->getShippingAddress()->getDiscountAmount());
            }
            $cart['price']['currency'] = $this->_getCurrency();
            if ($cart['price']['basePrice'] > 0.0) {
                $taxRate = (float)$quote->getShippingAddress()->getTaxAmount() / $quote->getSubtotal();
                $cart['price']['taxRate'] = round($taxRate, 3); // TODO: Find a better way
            }
            if ($quote->getShippingAmount()) {
                $cart['price']['shipping'] = (float)$quote->getShippingAmount();
            }
            if ($this->_extractShippingMethod($quote)) {
                $cart['price']['shippingMethod'] = $this->_extractShippingMethod($quote);
            }
            if ($quote->getShippingAddress()->getTaxAmount() && $quote->getSubtotal()) {
                $cart['price']['priceWithTax'] = (float)$quote->getShippingAddress()->getTaxAmount() + $this->getCurrentPrice($quote->getSubtotal(), false, false);
            } else {
                $cart['price']['priceWithTax'] = $cart['price']['basePrice'];
            }
            if ($quote->getData()['grand_total'] ) {
                $cart['price']['cartTotal'] = (float)$quote->getData()['grand_total'];
            } else {
                $cart['price']['cartTotal'] = $cart['price']['priceWithTax'];
            }
            // $cart['attributes'] = array();
            if ($cart['price']['basePrice'] === 0.0 && $cart['price']['cartTotal'] === 0.0 && $cart['price']['priceWithTax'] === 0.0) {
                unset($cart['price']);
            }

            // Line items
            $items = $quote->getAllVisibleItems();
            if (!$items && isset($cart['price'])) {
                if ($this->_debug) {
                    $cart['price']['testLog'] = "Second method used to retrieve cart items.";
                }

                // In case items were not retrieved for some reason
                $cartHelper = Mage::helper('checkout/cart');
                $items = $cartHelper->getCart()->getItems();
            }
            $cart['items'] = $this->_getLineItems($items, 'cart');
            if (empty($cart['items'])) {
                unset($cart['items']);
            }

            // The following are not used in W3C DDL but exist in Universal Variable:
            // $cart['subtotal']             = (float) $quote->getSubtotal();
            // $cart['tax']                  = (float) $quote->getShippingAddress()->getTaxAmount();
            // $cart['subtotal_include_tax'] = (boolean) $this->_doesSubtotalIncludeTax($quote, $cart['tax']);

            if ($cart_id || isset($cart['items']) || isset($cart['price'])) {
                $this->_cart = $cart;
            }
        } catch (Exception $e) {
        }
    }

    public function _doesSubtotalIncludeTax($order, $tax)
    {
        /*
          Conditions:
            - if tax is zero, then set to false
            - Assume that if grand total is bigger than total after subtracting shipping, then subtotal does NOT include tax
        */
        try {
            $grandTotalWithoutShipping = $order->getGrandTotal() - $order->getShippingAmount();
            if ($tax === 0 || $grandTotalWithoutShipping > $order->getSubtotal()) {
                return false;
            } else {
                return true;
            }
        } catch (Exception $e) {
        }
    }

    public function _extractShippingMethod($order)
    {
        try {
            $shipping_method = $order->getShippingMethod();
        } catch (Exception $e) {
        }
        return $shipping_method ? $shipping_method : '';
    }

    public function _setTransaction()
    {
        /*
          Spec:
            transaction {
              transactionID: "",
              profile: {
                profileInfo: {
                  profileID: "",
                  userName: "",
                  email: ""
                },
                address: {
                  line1: "",
                  line2: "",
                  city: "",
                  stateProvince: "",
                  postalCode: "",
                  country: ""
                },
                shippingAddress: {
                  line1: "",
                  line2: "",
                  city: "",
                  stateProvince: "",
                  postalCode: "",
                  country: ""
                }
              },
              total: {
                basePrice: 0,
                voucherCode: "",
                voucherDiscount: 0,
                currency: "USD",
                taxRate: 0,
                shipping: 0,
                shippingMethod: "",
                priceWithTax: 0,
                transactionTotal: 0
              },
              attributes: {},
              item: [
                {
                  productInfo
                  category
                  quantity
                  price
                  linkedProduct
                  attributes
                }
              ]
            }
         */

        try {
            $orderId = $this->_getCheckoutSession()->getLastOrderId();

            if ($orderId) {
                $transaction = array();
                $order = $this->_getSalesOrder()->load($orderId);

                // Get general details
                $transaction['transactionID'] = $order->getIncrementId();
                $transaction['total'] = array();
                $transaction['total']['currency'] = $this->_getCurrency();
                $transaction['total']['basePrice'] = (float)$order->getSubtotal();
                // $transaction['tax']               = (float) $order->getTaxAmount();
                // $transaction['subtotal_include_tax'] = $this->_doesSubtotalIncludeTax($order, $transaction['tax']);
                // $transaction['payment_type']         = $order->getPayment()->getMethodInstance()->getTitle();
                $transaction['total']['transactionTotal'] = (float)$order->getGrandTotal();

                $voucher = $order->getCouponCode();
                $transaction['total']['voucherCode'] = $voucher ? $voucher : "";
                $voucher_discount = -1 * $order->getDiscountAmount();
                $transaction['total']['voucherDiscount'] = $voucher_discount ? $voucher_discount : 0;

                $transaction['total']['shipping'] = (float)$order->getShippingAmount();
                $transaction['total']['shippingMethod'] = $this->_extractShippingMethod($order);

                // Get addresses
                $transaction['profile'] = array();
                if ($order->getBillingAddress()) {
                    $billingAddress = $order->getBillingAddress();
                    $transaction['profile']['address'] = $this->_getAddress($billingAddress);
                }
                if ($order->getShippingAddress()) {
                    $shippingAddress = $order->getShippingAddress();
                    $transaction['profile']['shippingAddress'] = $this->_getAddress($shippingAddress);
                }
                // Get items
                $items = $order->getAllItems();
                $line_items = $this->_getLineItems($items, 'transaction');
                $transaction['item'] = $line_items;

                $this->_transaction = $transaction;
            }
        } catch (Exception $e) {
        }
    }

    public function setDigitalDataLayer(Varien_Event_Observer $observer)
    {
        // W3C DDL
        //  - pageInstanceID
        //  - page
        //  - product[n]
        //  - cart
        //  - transaction
        //  - event[n]
        //  - component[n]
        //  - user[n]
        //  - privacyAccessCategories
        //  - version = "1.0"


        try {
            $triggered_messaging_digital_data_layer_enabled = (boolean)Mage::getStoreConfig('triggered_messaging/triggered_messaging_digital_data_layer_enabled');
            if ($triggered_messaging_digital_data_layer_enabled == 1) {

                // get layout handles
                $handles = Mage::app()->getLayout()->getUpdate()->getHandles();

                // allow DDL rendering for all pages or just specified handles
                if (! Mage::helper('digital_data_layer_main')->isAllPagesEnabled()){
                    $allowedPageHandles = Mage::helper('digital_data_layer_main')->getAllowedPagesHandlers();

                    // if the handle match is NOT found
                    if(! count(array_intersect($allowedPageHandles, $handles)) ){
                        //Zend_Debug::dump('NO MATCH - returning false');
                        return false;
                    }

                }

                $this->_debug = (boolean)Mage::getStoreConfig('triggered_messaging/triggered_messaging_digital_data_layer_debug_enabled');
                $this->_userGroupExp = (boolean)Mage::getStoreConfig('triggered_messaging/triggered_messaging_digital_data_layer_user_group_enabled');
                $this->_expAttr = explode(',', Mage::getStoreConfig('triggered_messaging/triggered_messaging_digital_data_layer_attributes_enabled'));
				$this->_stockExp = (int)Mage::getStoreConfig('triggered_messaging/triggered_messaging_digital_data_layer_stock_exposure');
				$this->_listLimit = Mage::getStoreConfig('triggered_messaging/triggered_messaging_digital_data_layer_prod_list_exposure');
				
                $this->_setUser();
                $this->_setPage();

                if ($this->_isProduct()) {
                    $this->_setProduct();
                }

                if ($this->_isCategory() || $this->_isSearch()) {
                    $this->_setListing();
                }

                if (!$this->_isConfirmation()) {
                    $this->_setCart();
                }

                if ($this->_isConfirmation()) {
                    $this->_setTransaction();
                }

                // Add script after content block, to grab products shown on category and search pages
                $layout = $observer->getEvent()->getLayout()->getUpdate();
                $layout->addHandle('tms_block_after_content');
            }
        } catch (Exception $e) {
        }

        return $this;
    }
}

?>
