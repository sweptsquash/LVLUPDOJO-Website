<?php

include(dirname(__FILE__) . '/init.php');

$SenFramework = new \SenFramework\SenFramework($senConfig);

class FilePondUpload {

    private $originalRequest;
	public $route;
    public $query;
    private $FilePond;

    public function __construct() {
        global $user, $request;

        header('Access-Control-Allow-Methods: OPTIONS, GET, DELETE, POST');

        if($user->data['is_registered']) {
            $this->originalRequest = $request->server('REQUEST_URI');
		
            $uri = null;
            
            if(($p = strpos($this->originalRequest, '?')) !== false)  {
                $q = substr($this->originalRequest, ($p + 1));
                    
                if(!empty($q)) {
                    $qa = explode('&amp;', $q);
                    
                    foreach($qa as $key => $value) {
                        $k = explode('=', $value);
                        
                        if(!empty($k)) {
                            $this->query[$k[0]] = urldecode($k[1]);	
                        }
                        
                        unset($k);
                    }
                    
                    unset($qa);
                }
                
                $uri = substr($this->originalRequest, 0, $p); // trim query string
                
                unset($p);
            }

            $this->route = array_values(array_filter(explode('/', strtolower(((!empty($uri)) ? $uri : $this->originalRequest)))));

            $this->FilePond = new \SenFramework\FilePond\RequestHandler;
            $this->FilePond->catchExceptions();            

            switch ($request->server('REQUEST_METHOD')) {
                case 'GET': self::handleGET(); break;
                case 'POST': self::handlePOST(); break;
                case 'DELETE': self::handleDELETE(); break;
            }
        } else {
            header($request->server("SERVER_PROTOCOL") . " 500 Internal Server Error", true, 500);
        }
    }

    /**
     * Routes, "fetch", "restore" and "load" requests to the matching functions
     */
    public function handleGET() {
        $handlers = array(
            'fetch' => array($this, 'handleFetch'),
            'restore' => array($this, 'handleRestore'),
            'load' => array($this, 'handleLoad')
        );

        foreach ($handlers as $param => $handler) {
            if (isset($this->query[$param])) {
                call_user_func($handler, $this->query[$param]);
            }
        }
    }

    /**
     * Handle loading of already saved files
     */
    public function handleLoad($id) {
        global $request;

        // Stop here if no id supplied
        if (empty($id)) {
            // Nope, Bad Request
            header($request->server("SERVER_PROTOCOL") . " 400 Bad Request", true, 400);

            return;
        }
        // 
        // In this example implementation the file id is simply the filename and 
        // we request the file from the uploads folder, it could very well be 
        // that the file should be fetched from a database or other system.
        //
        // Let's get the temp file content
        $url = explode('/', urldecode($id));

        $file = end($url);
        $path = rtrim(ABSPATH,'/').rtrim(str_replace($file, '', $id), '/');
        $id = $file;

        $file = $this->FilePond->getFile($id, $path);

        // Server error while reading the file
        if ($file === null) {
            // Nope, Bail out
            header($request->server("SERVER_PROTOCOL") . " 500 Internal Server Error", true, 500);

            return;
        }
        // Return file
        // Allow to read Content Disposition (so we can read the file name on the client side)
        header('Access-Control-Expose-Headers: Content-Disposition');
        header('Content-Type: ' . $file['type']);
        header('Content-Length: ' . $file['length']);
        header('Content-Disposition: inline; filename="' . $file['name'] . '"');
        echo $file['content'];
    }

    /**
     * Handle restoring of temporary files
     */
    public function handleRestore($id) {
        // Stop here if no id supplied
        if (empty($id)) {
            // Nope, Bad Request
            header($request->server("SERVER_PROTOCOL") . " 400 Bad Request", true, 400);

            return;
        }
        // Is this a valid id (should be same regex as client)
        if (!$this->FilePond->isFileId($id)) {
            // Nope, Bad Request
            header($request->server("SERVER_PROTOCOL") . " 400 Bad Request", true, 400);

            return;
        }
        // Let's get the temp file content
        $file = $this->FilePond->getTempFile($id);

        // No file returned, file probably not found
        if ($file === false) {
            // Nope, File not found
            header($request->server("SERVER_PROTOCOL") . " 404 Not Found", true, 404);

            return;
        }
        // Server error while reading the file
        if ($file === null) {
            // Nope, Bail out
            header($request->server("SERVER_PROTOCOL") . " 500 Internal Server Error", true, 500);

            return;
        }

        // Return file
        // Allow to read Content Disposition (so we can read the file name on the client side)
        header('Access-Control-Expose-Headers: Content-Disposition');
        header('Content-Type: ' . $file['type']);
        header('Content-Length: ' . $file['length']);
        header('Content-Disposition: inline; filename="' . $file['name'] . '"');
        echo $file['content'];
    }

    /**
     * Fetches data from a remote URL and returns it to the client
     */
    public function handleFetch($url) {
        global $request;

        // Stop here if no data supplied
        if (empty($url)) {
            // Nope, Bad Request
            header($request->server("SERVER_PROTOCOL") . " 400 Bad Request", true, 400);

            return;
        }
        // Is this a valid url
        if (!$this->FilePond->isURL($url)) {
            // Nope, Bad Request
            header($request->server("SERVER_PROTOCOL") . " 400 Bad Request", true, 400);

            return;
        }
        // Let's get the remote file content
        $response = $this->FilePond->getRemoteURLData($url);
        // Something went wrong
        if ($response === null) {
            // Nope, Probably a problem while fetching the resource
            header($request->server("SERVER_PROTOCOL") . " 500 Internal Server Error", true, 500);

            return;
        }
        // remote server returned invalid response
        if (!$response['success']) {
            // Clone response code and communicate to client
            http_response_code($response['code']);
            return;
        }
        
        // Return file
        header('Content-Type: ' . $response['type']);
        header('Content-Length: ' . $response['length']);
        echo $response['content'];
    }

    /**
     * Uploads a new file, file contents is supplied as POST body
     */
    public function handlePOST() {
        global $request;

        $field = NULL;
        $fields = $request->variable_names();

        foreach($fields as $key => $value) {
            if(preg_match('~[0-9]~',$value) === 1) {
                $number = preg_replace('/[^0-9]+/', '', $value);
                $value = preg_replace('/[0-9]/', '', $value);
            }

            switch($value) {
                case"filepond":
                    $field = 'filepond';
                break;
    
                case"bannerUpload":
                    $field = 'bannerUpload';
                break;
    
                case"thumbnailUpload":
                    $field = 'thumbnailUpload';
                break;

                case"avatarUpload":
                    $field = 'avatarUpload';
                break;

                case"mentorAvatarUpload":
                    $field = 'mentorAvatarUpload';
                break;

                case"lessonThumbnailUpload":
                    $field = 'lessonThumbnailUpload'.$number;
                break;

                case"materialWorkbookUpload":
                    $field = 'materialWorkbookUpload'.$number;
                break;
            }
        }

        if(!empty($field)) {
            // Get submitted field data item, will always be one item in case of async upload
            $items = $this->FilePond->loadFilesByField($field);

            // If no items, exit
            if (count($items) === 0) {
                // Something went wrong, most likely a field name mismatch
                header($request->server("SERVER_PROTOCOL") . " 400 Bad Request", true, 400);

                return;
            }
            // Returns plain text content
            header('Content-Type: text/plain');
            // Remove item from array Response contains uploaded file server id
            echo array_shift($items)->getId();
        } else {
            header($request->server("SERVER_PROTOCOL") . " 400 Bad Request", true, 400);
        }
    }

    /**
     * Removes a temp file, temp file id is supplied as DELETE request body
     */
    public function handleDELETE() {
        global $request;

        $id = file_get_contents('php://input');

        // test if id was supplied
        if (!isset($id)) {
            
            // Nope, Bad Request
            header($request->server("SERVER_PROTOCOL") . " 400 Bad Request", true, 400);

            return;
        }
        // Find the file and remove it from the server
        $success = $this->FilePond->deleteTempFile($id);
        // will always return success, client has no use for failure state
        // no content to return
        http_response_code(204);
    }
}

new FilePondUpload;

?>