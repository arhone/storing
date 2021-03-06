<?php declare(strict_types = 1);

namespace arhone\storing;

/**
 * Хранилище данных (ключ - значение) (PHP 7)
 *
 * Class StorageFileSystemAdapter
 * @package arhone\storing
 * @author Алексей Арх <info@arh.one>
 */
class StorageFileSystemAdapter implements StorageInterface {

    /**
     * Настройки класса
     *
     * @var array
     */
    protected $configuration = [
        'directory' => __DIR__ . '/storage'
    ];

    /**
     * StorageFileSystemAdapter constructor.
     * @param array $configuration
     */
    public function __construct (array $configuration) {

        $this->configure($configuration);

    }

    /**
     * Возвращает значение файла
     *
     * @param string $key
     * @return mixed
     */
    public function get (string $key) {

        $path = $this->getPath($key);
        if (is_file($path)) {

            $data = unserialize(file_get_contents($path));
            if (!empty($data['remove']) && $data['remove'] < time()) {

                return false;

            }

            return $data['data'] ?? false;

        }

        return null;

    }

    /**
     * Записывает значение в файл
     *
     * @param string $key
     * @param $data
     * @return bool
     */
    public function set (string $key, $data) : bool {

        $path = $this->getPath($key);
        $dir  = dirname($path);
        if (!is_dir($dir)) {

            mkdir($dir, 0700, true);

        }

        return file_put_contents($path, serialize($data), LOCK_EX) == true;

    }

    /**
     * Удаление значения
     *
     * @param string $key
     * @return bool
     */
    public function delete (string $key) : bool {

        return $this->deleteRecursive($this->getPath($key)) == true;

    }

    /**
     * Рекурсивное удаление файлов
     *
     * @param $path
     * @return bool
     */
    protected function deleteRecursive ($path) : bool {

        if (is_dir($path)) {

            foreach (scandir($path) as $file) {

                if ($file != '.' && $file != '..') {

                    if (is_file($file)) {

                        unlink($file);

                    } else {

                        $this->deleteRecursive($path . DIRECTORY_SEPARATOR . $file);

                    }

                }

            }

            if(count(glob($path . '*')) ? true : false) {

                return rmdir($path);

            }

        } elseif (is_file($path)) {

            return unlink($path);

        }

        return false;

    }

    /**
     * Возврщает путь до файла
     *
     * @param string $key
     * @return string
     */
    protected function getPath (string $key) : string {

        $path = $this->configuration['directory'] . DIRECTORY_SEPARATOR . str_replace('.', DIRECTORY_SEPARATOR, $key);
        if (is_dir($path) || is_file($path)) {

            return $path;

        } else {

            $dir  = dirname($path);
            $hash = md5(basename($path));
            return $dir . DIRECTORY_SEPARATOR . '.' . $hash[0] . $hash[1] . DIRECTORY_SEPARATOR . '.' . $hash[2] . $hash[3] . DIRECTORY_SEPARATOR . '.' . $hash;

        }
    }

    /**
     * Проверка ключа
     *
     * @param string $key
     * @return bool
     */
    public function has (string $key) : bool {

        return !empty($this->getPath($key));

    }

    /**
     * Наполнение контейнера
     *
     * @param array $data
     * @return bool
     */
    public function fill (array $data) : bool {

        foreach ($data as $key => $value) {
            $this->set($key, $value);
        }

        return true;

    }

    /**
     * Очистить контейнер
     *
     * @return bool
     */
    public function clear () : bool {

        return $this->deleteRecursive($this->configuration['directory']) == true;

    }

    /**
     * Задаёт конфигурацию
     *
     * @param array $configuration
     * @return array
     */
    public function configure (array $configuration = []) : array {

        return $this->configuration = array_merge($this->configuration, $configuration);

    }


}