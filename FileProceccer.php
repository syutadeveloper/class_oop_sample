<?php
class User {
    public $name;
    public $email;
    public $age;

    public function __construct($name, $email, $age) {
        $this->name = $name;
        $this->email = $email;
        $this->age = $age;
    }
}

interface FileParser {
    public function parse($content);
}

class CsvParser implements FileParser {
    public function parse($content) {
        $rows = array_map("str_getcsv", explode("\n", $content));
        $users = [];
        foreach ($rows as $row) {
            if (count($row) === 3) {
                $users[] = new User($row[0], $row[1], $row[2]);
            }
        }
        return $users;
    }
}

class JsonParser implements FileParser {
    public function parse($content) {
        $data = json_decode($content, true);
        $users = [];
        foreach ($data as $item) {
            if (isset($item['name'], $item['email'], $item['age'])) {
                $users[] = new User($item['name'], $item['email'], $item['age']);
            }
        }
        return $users;
    }
}

class MyXmlParser implements FileParser {
    public function parse($content) {
        $xml = simplexml_load_string($content);
        $users = [];
        
        foreach ($xml->user as $user) {
            if (isset($user->name, $user->email, $user->age)) {
                $users[] = new User((string)$user->name, (string)$user->email, (int)$user->age);
            }
        }
        return $users;
    }
}

class ParserFactory {
    public static function createParser($type) {
        switch ($type) {
            case "csv":
                return new CsvParser();
            case "json":
                return new JsonParser();
            case "xml":
                return new MyXmlParser();
            default:
                throw new Exception("Unsupported file type");
        }
    }
}

interface OutputHandler {
    public function output($data);
}

class ScreenOutput implements OutputHandler {
    public function output($data) {
        $output = "<table><thead><tr><th>Name</th><th>Email</th><th>Age</th></tr></thead><tbody>";
        foreach ($data as $user) {
            $output .= "<tr><td>{$user->name}</td><td>{$user->email}</td><td>{$user->age}</td></tr>";
        }
        $output .= "</tbody></table>";
        return $output;
    }
}

class EmailOutput implements OutputHandler {
    private $email;
    
    public function __construct($email) {
        $this->email = $email;
    }
    
    public function output($data) {
        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            return "Недействительный адрес электронной почты.";
        }
        
        $message = "Name, Email, Age\n";
        foreach ($data as $user) {
            $message .= "{$user->name}, {$user->email}, {$user->age}\n";
        }
        
        mail($this->email, "Результат обработки файла", $message);
        return "Отправлено по электронной почте.";
    }
}

class OutputFactory {
    public static function createOutputHandler($type, $email = "") {
        switch ($type) {
            case "screen":
                return new ScreenOutput();
            case "email":
                return new EmailOutput($email);
            default:
                throw new Exception("Unsupported output type");
        }
    }
}

class FileProcessor {
    private $parser;
    private $outputHandler;
    private $file;

    public function __construct(FileParser $parser, OutputHandler $outputHandler, $file) {
        $this->parser = $parser;
        $this->outputHandler = $outputHandler;
        $this->file = $file;
    }

    public function process() {
        if ($this->file["error"] !== UPLOAD_ERR_OK) {
            return "Ошибка загрузки файла.";
        }

        $content = file_get_contents($this->file["tmp_name"]);
        $parsedData = $this->parser->parse($content);
        
        return $this->outputHandler->output($parsedData);
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        $parser = ParserFactory::createParser($_POST["inputType"]);
        $outputHandler = OutputFactory::createOutputHandler($_POST["outputType"], $_POST["email"] ?? "");
        
        $processor = new FileProcessor($parser, $outputHandler, $_FILES["file"]);
        echo $processor->process();
    } catch (Exception $e) {
        echo "Ошибка:  " . $e->getMessage();
    }
}
