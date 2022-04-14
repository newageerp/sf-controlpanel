<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

namespace Newageerp\SfControlpanel\Controller;

use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route(path="/app/nae-core/config-db")
 */
class ConfigDbController extends ConfigBaseController
{
    /**
     * @Route(path="/variablesSave")
     * @OA\Post (operationId="NaeConfigDbVariablesSave")
     */
    public function saveVariable(Request $request)
    {
        $request = $this->transformJsonBody($request);

        $element = $request->get('element');

        $db = new \SQLite3($this->getLocalDbFile(), SQLITE3_OPEN_READWRITE);

        if ($element['id'] === 0) {
            $sql = "insert into variables (`text`, slug) values (:text, :slug)";
        } else {
            $sql = "update variables set `text` = :text, slug = :slug  where id = :id";
        }

        $stmt = $db->prepare($sql);

        if ($element['id'] > 0) {
            $stmt->bindValue(':id', $element['id'], SQLITE3_INTEGER);
        }

        $stmt->bindValue(':text', $element['text']);
        $stmt->bindValue(':slug', $element['slug']);

        $stmt->execute();

        return $this->json(['success' => 1]);
    }

    /**
     * @Route(path="/variablesRemove")
     * @OA\Post (operationId="NaeConfigDbVariablesRemove")
     */
    public function removeVariable(Request $request)
    {
        $request = $this->transformJsonBody($request);

        $id = $request->get('id');

        $db = new \SQLite3($this->getLocalDbFile(), SQLITE3_OPEN_READWRITE);

        $sql = "delete from variables where id = :id";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->execute();

        return $this->json(['success' => 1]);
    }


    /**
     * @Route(path="/variables")
     * @OA\Post (operationId="NaeConfigDbVariablesList")
     */
    public function listVariables()
    {
        $db = new \SQLite3($this->getLocalDbFile());

        $sql = "SELECT 
                    variables.id, variables.`text`, variables.slug
                FROM variables";
        $result = $db->query($sql);

        $_output = [];
        while ($data = $result->fetchArray(SQLITE3_ASSOC)) {
            $_output[] = $data;
        }

        return $this->json(['data' => $_output]);
    }

    /*********** */

    /**
     * @Route(path="/userPermissionsSave")
     * @OA\Post (operationId="NaeConfigDbUserPermissionsSave")
     */
    public function saveUserPermission(Request $request)
    {
        $request = $this->transformJsonBody($request);

        $element = $request->get('element');

        $db = new \SQLite3($this->getLocalDbFile(), SQLITE3_OPEN_READWRITE);

        if ($element['id'] === 0) {
            $sql = "insert into user_permissions (`title`, slug) values (:title, :slug)";
        } else {
            $sql = "update user_permissions set `title` = :title, slug = :slug  where id = :id";
        }

        $stmt = $db->prepare($sql);

        if ($element['id'] > 0) {
            $stmt->bindValue(':id', $element['id'], SQLITE3_INTEGER);
        }

        $stmt->bindValue(':title', $element['title']);
        $stmt->bindValue(':slug', $element['slug']);

        $stmt->execute();

        return $this->json(['success' => 1]);
    }

    /**
     * @Route(path="/userPermissionsRemove")
     * @OA\Post (operationId="NaeConfigDbUserPermissionsRemove")
     */
    public function removeUserPermission(Request $request)
    {
        $request = $this->transformJsonBody($request);

        $id = $request->get('id');

        $db = new \SQLite3($this->getLocalDbFile(), SQLITE3_OPEN_READWRITE);

        $sql = "delete from user_permissions where id = :id";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->execute();

        return $this->json(['success' => 1]);
    }


    /**
     * @Route(path="/user-permissions")
     * @OA\Post (operationId="NaeConfigDbUserPermissionsList")
     */
    public function listUserPermissions()
    {
        $db = new \SQLite3($this->getLocalDbFile());

        $sql = "SELECT 
                    user_permissions.id, user_permissions.title as title, user_permissions.slug as slug
                FROM user_permissions";
        $result = $db->query($sql);

        $_output = [];
        while ($data = $result->fetchArray(SQLITE3_ASSOC)) {
            $_output[] = $data;
        }

        return $this->json(['data' => $_output]);
    }

    /*********** */


    /**
     * @Route(path="/pdfSave")
     * @OA\Post (operationId="NaeConfigDbPdfsSave")
     */
    public function savePdf(Request $request)
    {
        $request = $this->transformJsonBody($request);

        $element = $request->get('element');

        $db = new \SQLite3($this->getLocalDbFile(), SQLITE3_OPEN_READWRITE);

        if ($element['id'] === 0) {
            $sql = "insert into pdfs (`title`, template, sort, skipList, entity, skipWithoutSign) values (:title, :template, :sort, :skipList, :entity, :skipWithoutSign)";
        } else {
            $sql = "update pdfs set `title` = :title, template = :template, sort = :sort, skipList = :skipList, skipWithoutSign = :skipWithoutSign, entity = :entity  where id = :id";
        }

        $stmt = $db->prepare($sql);

        if ($element['id'] > 0) {
            $stmt->bindValue(':id', $element['id'], SQLITE3_INTEGER);
        }

        $stmt->bindValue(':title', $element['title']);
        $stmt->bindValue(':template', $element['template']);
        $stmt->bindValue(':sort', $element['sort'], SQLITE3_INTEGER);
        $stmt->bindValue(':skipList', $element['skipList'], SQLITE3_INTEGER);
        $stmt->bindValue(':entity', $element['entity'], SQLITE3_INTEGER);
        $stmt->bindValue(':skipWithoutSign', $element['skipWithoutSign'], SQLITE3_INTEGER);

        $stmt->execute();

        return $this->json(['success' => 1]);
    }

    /**
     * @Route(path="/pdfRemove")
     * @OA\Post (operationId="NaeConfigDbPdfsRemove")
     */
    public function removePdf(Request $request)
    {
        $request = $this->transformJsonBody($request);

        $id = $request->get('id');

        $db = new \SQLite3($this->getLocalDbFile(), SQLITE3_OPEN_READWRITE);

        $sql = "delete from pdfs where id = :id";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->execute();

        return $this->json(['success' => 1]);
    }


    /**
     * @Route(path="/pdfs")
     * @OA\Post (operationId="NaeConfigDbPdfsList")
     */
    public function listPdfs(Request $request)
    {
        $request = $this->transformJsonBody($request);

        $db = new \SQLite3($this->getLocalDbFile());

        $sql = "SELECT 
                    pdfs.id, pdfs.title, pdfs.template, pdfs.sort, pdfs.skipList, pdfs.entity, pdfs.skipWithoutSign,
                    entities.slug || ' (' || entities.titleSingle || ')' as entity_title
                FROM pdfs 
                left join entities on pdfs.entity = entities.id
                WHERE 1 = 1 
                ";

        if ($request->get('id')) {
            $sql .= ' AND pdfs.id = ' . (int)$request->get('id') . ' ';
        }

        $result = $db->query($sql);

        $_output = [];
        while ($data = $result->fetchArray(SQLITE3_ASSOC)) {
            $_output[] = $data;
        }

        return $this->json(['data' => $_output]);
    }

    /*********** */

    /**
     * @Route(path="/statusSave")
     * @OA\Post (operationId="NaeConfigDbStatusesSave")
     */
    public function saveStatus(Request $request)
    {
        $request = $this->transformJsonBody($request);

        $element = $request->get('element');

        $db = new \SQLite3($this->getLocalDbFile(), SQLITE3_OPEN_READWRITE);

        if ($element['id'] === 0) {
            $sql = "insert into statuses (`text`, status, entity, type, color, brightness) values (:text, :status, :entity, :type, :color, :brightness)";
        } else {
            $sql = "update statuses set `text` = :text, status = :status, entity = :entity, type = :type, color = :color, brightness = :brightness  where id = :id";
        }

        $stmt = $db->prepare($sql);

        if ($element['id'] > 0) {
            $stmt->bindValue(':id', $element['id'], SQLITE3_INTEGER);
        }

        $stmt->bindValue(':text', $element['text']);
        $stmt->bindValue(':status', $element['status'], SQLITE3_INTEGER);
        $stmt->bindValue(':entity', $element['entity'], SQLITE3_INTEGER);
        $stmt->bindValue(':type', $element['type']);
        $stmt->bindValue(':color', $element['color']);
        $stmt->bindValue(':brightness', $element['brightness']);

        $stmt->execute();

        return $this->json(['success' => 1]);
    }

    /**
     * @Route(path="/statusRemove")
     * @OA\Post (operationId="NaeConfigDbStatusesRemove")
     */
    public function removeStatus(Request $request)
    {
        $request = $this->transformJsonBody($request);

        $id = $request->get('id');

        $db = new \SQLite3($this->getLocalDbFile(), SQLITE3_OPEN_READWRITE);

        $sql = "delete from statuses where id = :id";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->execute();

        return $this->json(['success' => 1]);
    }


    /**
     * @Route(path="/statuses")
     * @OA\Post (operationId="NaeConfigDbStatusesList")
     */
    public function listStatuses()
    {
        $db = new \SQLite3($this->getLocalDbFile());

        $sql = "SELECT 
                    statuses.id, statuses.text, statuses.entity, statuses.status, statuses.type, statuses.color, statuses.brightness,
                    entities.slug || ' (' || entities.titleSingle || ')' as entity_title
                FROM statuses 
                left join entities on statuses.entity = entities.id
                ";
        $result = $db->query($sql);

        $_output = [];
        while ($data = $result->fetchArray(SQLITE3_ASSOC)) {
            $_output[] = $data;
        }

        return $this->json(['data' => $_output]);
    }

    /**
     * @Route(path="/entitySave")
     * @OA\Post (operationId="NaeConfigDbEntitiesSave")
     */
    public function saveEntity(Request $request)
    {
        $request = $this->transformJsonBody($request);

        $element = $request->get('element');

        $db = new \SQLite3($this->getLocalDbFile(), SQLITE3_OPEN_READWRITE);

        $sql = "update entities set titleSingle = :titleSingle, titlePlural = :titlePlural, required = :required, scopes = :scopes where id = :id";

        $stmt = $db->prepare($sql);

        $stmt->bindValue(':id', $element['id'], SQLITE3_INTEGER);
        $stmt->bindValue(':titleSingle', $element['titleSingle']);
        $stmt->bindValue(':titlePlural', $element['titlePlural']);
        $stmt->bindValue(':required', $element['required']);
        $stmt->bindValue(':scopes', $element['scopes']);

        $stmt->execute();

        return $this->json(['success' => 1]);
    }

    /**
     * @Route(path="/entities")
     * @OA\Post (operationId="NaeConfigDbEntitiesList")
     */
    public function listEntities(Request $request)
    {
        $request = $this->transformJsonBody($request);

        $db = new \SQLite3($this->getLocalDbFile());

        $sql = "SELECT 
                    entities.id,
                    entities.slug,
                    entities.titleSingle,
                    entities.titlePlural,
                    entities.className,
                    entities.required,
                    entities.scopes
                FROM entities 
                WHERE 1 = 1
                ";

        if ($request->get('id')) {
            $sql .= ' AND entities.id = ' . (int)$request->get('id') . ' ';
        }

        $result = $db->query($sql);

        $_output = [];
        while ($data = $result->fetchArray(SQLITE3_ASSOC)) {
            $_output[] = $data;
        }

        return $this->json(['data' => $_output]);
    }

    /**
     * @Route(path="/propertySave")
     * @OA\Post (operationId="NaeConfigDbPropertiesSave")
     */
    public function saveProperty(Request $request)
    {
        $request = $this->transformJsonBody($request);

        $element = $request->get('element');

        $db = new \SQLite3($this->getLocalDbFile(), SQLITE3_OPEN_READWRITE);

        $sql = "update properties set title = :title, description = :description where id = :id";

        $stmt = $db->prepare($sql);

        $stmt->bindValue(':id', $element['id'], SQLITE3_INTEGER);
        $stmt->bindValue(':title', $element['title']);
        $stmt->bindValue(':description', $element['description']);

        $stmt->execute();

        return $this->json(['success' => 1]);
    }

    /**
     * @Route(path="/propertyRemove")
     * @OA\Post (operationId="NaeConfigDbPropertiesRemove")
     */
    public function removeProperty(Request $request)
    {
        $request = $this->transformJsonBody($request);

        $id = $request->get('id');

        $db = new \SQLite3($this->getLocalDbFile(), SQLITE3_OPEN_READWRITE);

        $sql = "delete from properties where id = :id";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->execute();

        return $this->json(['success' => 1]);
    }

    /**
     * @Route(path="/properties")
     * @OA\Post (operationId="NaeConfigDbPropertiesList")
     */
    public function listProperties(Request $request)
    {
        $request = $this->transformJsonBody($request);

        $rSchema = (int)$request->get('schema');
        $schema = $rSchema ? $rSchema : 0;

        $db = new \SQLite3($this->getLocalDbFile());

        $sql = "SELECT 
                    properties.id,
                    properties.key,
                    properties.title,
                    properties.type,
                    properties.typeFormat,
                    properties.description,
                    properties.entity,
                    properties.isDb,
                    properties.dbType,
                    properties.`as`,
                    properties.additionalProperties,
                    entities.slug || ' (' || entities.titleSingle || ')' as entity_title
                FROM properties 
                left join entities on properties.entity = entities.id
                WHERE 1 = 1
                ";
        if ($schema > 0) {
            $sql .= " AND properties.entity = " . $schema . " ";
        }

        $result = $db->query($sql);

        $_output = [];
        while ($data = $result->fetchArray(SQLITE3_ASSOC)) {
            $_output[] = $data;
        }

        return $this->json(['data' => $_output]);
    }

    /**
     * @Route(path="/enums")
     * @OA\Post (operationId="NaeConfigDbEnumsList")
     */
    public function listEnums(Request $request)
    {
        $request = $this->transformJsonBody($request);

        $db = new \SQLite3($this->getLocalDbFile());

        $sql = "SELECT 
                    enums.id, enums.title, enums.value, enums.entity, enums.property, enums.sort,
                    entities.slug || ' (' || entities.titleSingle || ')' as entity_title,
                    properties.key || ' (' || properties.title || ')' as property_title
                FROM enums 
                left join entities on enums.entity = entities.id
                left join properties on enums.property = properties.id
                WHERE 1 = 1 
                ";

        if ($request->get('id')) {
            $sql .= ' AND enums.id = ' . (int)$request->get('id') . ' ';
        }

        $result = $db->query($sql);

        $_output = [];
        while ($data = $result->fetchArray(SQLITE3_ASSOC)) {
            $_output[] = $data;
        }

        return $this->json(['data' => $_output]);
    }

    /**
     * @Route(path="/enumRemove")
     * @OA\Post (operationId="NaeConfigDbEnumsRemove")
     */
    public function removeEnum(Request $request)
    {
        $request = $this->transformJsonBody($request);

        $id = $request->get('id');

        $db = new \SQLite3($this->getLocalDbFile(), SQLITE3_OPEN_READWRITE);

        $sql = "delete from enums where id = :id";

        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
        $stmt->execute();

        return $this->json(['success' => 1]);
    }

    /**
     * @Route(path="/enumSave")
     * @OA\Post (operationId="NaeConfigDbEnumsSave")
     */
    public function saveEnum(Request $request)
    {
        $request = $this->transformJsonBody($request);

        $element = $request->get('element');

        $db = new \SQLite3($this->getLocalDbFile(), SQLITE3_OPEN_READWRITE);
        $db->busyTimeout(5 * 1000);

        if ($element['id'] === 0) {
            $sql = "insert into enums (title, value, sort, entity, property) values (:title, :value, :sort, :entity, :property)";
        } else {
            $sql = "update enums set title = :title, value = :value, sort = :sort, entity = :entity, property = :property where id = :id";
        }

        $stmt = $db->prepare($sql);

        if ($element['id'] > 0) {
            $stmt->bindValue(':id', $element['id'], SQLITE3_INTEGER);
        }

        $stmt->bindValue(':title', $element['title']);
        $stmt->bindValue(':value', $element['value']);
        $stmt->bindValue(':sort', $element['sort'], SQLITE3_INTEGER);
        $stmt->bindValue(':entity', $element['entity'], SQLITE3_INTEGER);
        $stmt->bindValue(':property', $element['property'], SQLITE3_INTEGER);
        $stmt->execute();

        $db->close();
        unset($db);

        return $this->json(['success' => 1]);
    }

    /**
     * @Route(path="/test")
     */
    public function test()
    {
        $db = new \SQLite3($this->getLocalDbFile());

        $sql = "SELECT name FROM sqlite_master WHERE type='table';";
        $result = $db->query($sql);

        $_output = [];
        while ($data = $result->fetchArray()) {
            $_output[] = $data;
        }

        return $this->json($_output);
    }
}
