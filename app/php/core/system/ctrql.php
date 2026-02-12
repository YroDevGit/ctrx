<?php //route: ctr/ctrql

//Add codes here...

use Classes\Collection;
use Classes\DB;
use Classes\File;
use Classes\Request;
use Classes\Response;
use Classes\Validator;
use Classes\Ctrql;
use Classes\Ctrx;
use Classes\TimeZone;

/**
 * This is CodeTazeR CTRQL - for direct transaction
 * easy development as frontend can stand alone without any hassle.
 * created: January 2 2026
 * - CodeYro : Tyrone Limen Malocon.
 */


/**
 * Initialization area...
 */

$action = Request::ql("action");
$param = Request::ql("param") ?? [];
$table = Request::ql("table");
$encodeImages = Request::ql("encodeImages");
$extra = Request::ql("extra");
$accept = Request::ql("columns") ?? "*";
$update = Request::ql("update");
$query =  Request::ql("query");
$validation = Request::ql("validation");
$validationType = Request::ql("validationType") ?? "default";
$unique = Request::ql("unique");
$function = Request::ql("function");
$realtime = Request::ql("realtime");

if($realtime){
    if(is_array($realtime)){
        foreach($realtime as $f=>$v){
            $param[$v] = now();
        }
    }
}

/**
 * setup limit request per minute
 * default is 10
 */
$limit_request_per_minute = 10;
Ctrx::x_rate_limit($limit_request_per_minute, 60, "ctrql_" . $action);

/**
 * Check if ctrql is active
 */

 $activated = Ctrql::isActive();
 if(! $activated){
    Response::code(unauthorized_code)->message("Sorry. ctrql is currently disabled or user access is expired.!")->send(unauthorized_code);
 }

/**
 * Access filtering
 */

Ctrql::check_table($table, $action);

/**
 * Validation area...
 */
if ($validation) {
    if (! $validationType) {
        Response::code(badrequest_code)->message("ctrql: validationType is required.!")->send(badrequest_code);
    }
    if (! is_array($validation)) {
        Response::code(badrequest_code)->message("ctrql: validation should be an Object/array")->send(badrequest_code);
    }
    $dim = array_is_list($validation);
    if ($dim) {
        foreach ($validation as $d => $v) {
            $exp = explode("||", $v);
            $name = $exp[0] ?? null;
            $label = $exp[1] ?? $name;
            $pst = Validator::check($v, $label, "required", $param);
        }
    } else {
        foreach ($validation as $d => $v) {
            $exp = explode("||", $d);
            $name = $exp[0] ?? null;
            $label = $exp[1] ?? $name;
            $pst = Validator::check($name, $label, $v, $param);
        }
    }

    if (Validator::failed()) {
        $errors = Validator::errors();
        if ($errors) {
            if ($validationType == "default") {
                foreach ($errors as $k => $v) {
                    Response::code(422)->message($v)->var(["field" => $k])->send();
                }
            }
            if ($validationType == "detailed") {
                Response::code(422)->message("Validation failed.!")->errors($errors)->send();
            }
        }
    }
}

if (! $action) {
    Response::code(badrequest_code)->message("ctrql: action field is required.!")->send(badrequest_code);
}

if ($action == "disable") {
    Ctrql::disable();
    Response::code(success_code)->message("Yes.! ctrql disabled.")->send();
}

if ($action == "query") {
    if (! Ctrql::checkAccess("Q")) Response::code(unauthorized_code)->message("ctrql: User is not possible to use query function.!")->send(unauthorized_code);
    $result = DB::query($query, $param);
    if ($encodeImages) {
        $result = File::encode_blob($result, $encodeImages);
    }
    if ($accept == "*") {
        Response::code(success_code)->message("OK")->var(["empty" => $result ? false : true])->data($result)->send();
    }
    $result = Collection::data($result)->get($accept)->exec();
    Response::code(success_code)->message("OK")->data($result)->var(["empty" => $result ? false : true])->send();
}

if ($action == "model") {
    if (! Ctrql::checkAccess("M")) Response::code(unauthorized_code)->message("ctrql: User is not possible to use model function.!")->send(unauthorized_code);
    if (! $function) Response::code(badrequest_code)->message("ctrql: function field is required.!")->send(badrequest_code);
    $exp = explode("/", $function);
    $function = $exp[0] ?? "";
    $method = $exp[1] ?? "";
    $function = ucfirst($function);
    $file = "_backend/model/$function.php";
    if (! file_exists($file)) Response::code(badrequest_code)->message("ctrql: model '$function' not found.!")->send(badrequest_code);
    include_once $file;
    $class = "Models\\$function";
    if (! class_exists($class)) Response::code(badrequest_code)->message("ctrql: model class '$class' not found.!")->send(badrequest_code);
    $mod = new $class();
    try {
        $value = null;
        if ($param) {
            if (! is_array($param)) Response::code(badrequest_code)->message("ctrql: param should be array list.!")->send(badrequest_code);
            if (! array_is_list($param)) {
                Response::code(badrequest_code)->message("ctrql: param should be array list: []")->send(badrequest_code);
            }
            $value = $mod->$method(...$param);
        } else {
            $value = $mod->$method();
        }
        Response::code(success_code)->message("OK")->var(["value" => $value, "model" => "$function/$method"])->send();
    } catch (Throwable $e) {
        Response::code(badrequest_code)->message("ctrql: model error '$function/$method' - " . $e->getMessage())->send(badrequest_code);
    }
}

if (! $table) {
    Response::code(badrequest_code)->message("ctrql: table field is required")->send(badrequest_code);
}

if ($unique) {
    if (is_string($unique)) {
        $exp = explode("||", $unique);
        $unique = $exp[0] ?? null;
        $label = $exp[1] ?? $unique;
        if (! isset($param[$unique])) {
            Response::code(badrequest_code)->message("ctrql: $unique field not found @ request body.!")->send(badrequest_code);
        }
        $value = $param[$unique] ?? null;
        $msg = $exp[2] ?? "$label '$value' is already exist.!";
        $find = DB::findOne($table, [$unique => $value]);
        if ($find) {
            Response::code(failed_code)->message($msg)->send();
        }
    } else if (is_array($unique)) {
        foreach ($unique as $u => $v) {
            $unique = $v;
            $exp = explode("||", $unique);
            $unique = $exp[0] ?? null;
            $label = $exp[1] ?? $unique;
            if (! isset($param[$unique])) {
                Response::code(badrequest_code)->message("ctrql: $unique field not found @ request body.!")->send(badrequest_code);
            }
            $value = $param[$unique] ?? null;
            $msg = $exp[2] ?? "is already exist.!";
            $find = DB::findOne($table, [$unique => $value]);
            if ($find) {
                Response::code(failed_code)->message("$label '$value' $msg")->send();
            }
        }
    }
}

if ($action == "create" || $action == "insert") {
    if (! Ctrql::checkAccess("C")) Response::code(unauthorized_code)->message("ctrql: User is not possible to use insert/create function.!")->send(unauthorized_code);
    $id = DB::insert($table, $param);
    Response::code(success_code)->message("OK")->var(["_id" => $id])->data($param)->send();
} else if ($action == "read" || $action == "select" || $action == "find" || $action == "get" || $action == "findOne") {
    if (! Ctrql::checkAccess("R")) Response::code(unauthorized_code)->message("ctrql: User is not possible to use read/select function.!")->send(unauthorized_code);
    $result = [];
    if ($action == "findOne") {
        $result = DB::findOne($table, $param, $extra);
    } else {
        $result = DB::find($table, $param, $extra);
    }
    if ($encodeImages) {
        $result = File::encode_blob($result, $encodeImages);
    }
    $filter = Ctrql::get_hidden_table_columns($table);
    $result = Collection::data($result)->except($filter)->exec();
    if ($accept == "*") {
        Response::code(success_code)->message("OK")->var(["empty" => $result ? false : true])->data($result)->send();
    }
    $result = Collection::data($result)->get($accept)->exec();
    Response::code(success_code)->message("OK")->data($result)->var(["empty" => $result ? false : true])->send();
} else if ($action == "delete") {
    if (! Ctrql::checkAccess("D")) Response::code(unauthorized_code)->message("ctrql: User is not possible to use delete function.!")->send(unauthorized_code);
    if (! $param) {
        Response::code(badrequest_code)->message("ctrql: param/where field is required.!")->send(badrequest_code);
    }
    $result = DB::delete($table, $param);
    Response::code(success_code)->message("OK")->var(["rows" => $result ?? 0])->send();
} else if ($action == "update") {
    if (! Ctrql::checkAccess("U")) Response::code(unauthorized_code)->message("ctrql: User is not possible to use update function.!")->send(unauthorized_code);
    if (! $update) {
        Response::code(badrequest_code)->message("ctrql: update field is required.!")->send(badrequest_code);
    }
    if (! $param) {
        Response::code(badrequest_code)->message("ctrql: param/where field is required.!")->send(badrequest_code);
    }

    $result = DB::update($table, $update, $param);
    Response::code(success_code)->message("OK")->var(["rows" => $result ?? 0])->send();
} else if ($action == "count") {
    if (! Ctrql::checkAccess("R")) Response::code(unauthorized_code)->message("ctrql: User is not possible to use read/count function.!")->send(unauthorized_code);
    $result = DB::find($table, $param, $extra);
    if ($result) {
        Response::code(success_code)->message("OK")->count(sizeof($result))->send();
    } else {
        Response::code(success_code)->message("OK")->count(0)->send();
    }
} else {
    Response::code(badrequest_code)->message("Unknown action '$action'.!")->send(badrequest_code);
}
