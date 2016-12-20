<?php
/**
* PHP класс для отправки e-mail.
* 
* @author http://snipp.ru/
* @version 1.0
*/
class Mail
{
	/**
     * От кого.
	 * 
	 * @var string
	 */
	public $from = '';
	
	/**
     * Кому.
	 * 
	 * @var array
	 */
	public $to = array();

	/**
     * Тема.
	 * 
	 * @var string
	 */
	public $subject = '';
	
	/**
     * Текст.
	 * 
	 * @var string
	 */
	public $body = '';

	/**
     * Массив файлов.
	 * 
	 * @var array
	 */
	public $files = array();

	/**
     * Делать дамп письма.
	 * 
	 * @var bool
	 */
	public $dump = false;
	
	/**
     * Директория куда сохранять дампы писем.
	 * 
	 * @var string
	 */
	public $dump_path = '';

	/**
     * Конструктор.
	 * 
	 * @return void
	 */
	public function __construct()
	{
		if (empty($this->dump_path)) {
			$this->dump_path = dirname(__FILE__) . '/sendmail';
		}
	}

	/**
	 * Проверка существования файла.
	 * Если дериктория не существует - пытается её создать.
	 * Если файл существует - к концу файла приписывает префикс.
	 * 
	 * @param string $filename
	 * @return string
	 */
	private function safeFile($filename)
	{
		$dir = dirname($filename);
		if (!is_dir($dir)) {
			mkdir($dir, 0777, true);
		}

		$info   = pathinfo($filename);
		$name   = $dir . '/' . $info['filename']; 
		$ext    = (empty($info['extension'])) ? '' : '.' . $info['extension'];
		$prefix = '';

		if (is_file($name . $ext)) {
			$i = 1;
			$prefix = '_' . $i;
			while (is_file($name . $prefix . $ext)) {
				$prefix = '_' . ++$i;
			}
		}

		return $name . $prefix . $ext;
	}	

	/**
     * От кого.
	 * 
	 * @param string $email
	 * @param string $name
	 * @return void
	 */
	public function from($email, $name = null)
	{
		$this->from = (empty($name)) ? $email : '=?UTF-8?B?' . base64_encode($name) . '?= <' . $email . '>';
	}

	/**
     * Кому.
	 * 
	 * @param string $email
	 * @param string $name
	 * @return void
	 */
	public function to($email, $name = null)
	{
		$this->to = array();

		if (!empty($email)) {
			$emails = explode(',', $email);
			foreach ($emails as $row) {
				$row = trim($row);
				if (!empty($row)) {
					$this->to[] = (empty($name)) ? $row : '=?UTF-8?B?' . base64_encode($name) . '?= <' . $row . '>';
				}
			}
		}
	}

	/**
     * Отправка
	 * 
	 * @return bool
	 */
	public function send()
	{
		if (empty($this->to)) {
			return false;
		}

		// Тема письма.
		$subject = (empty($this->subject)) ? 'No subject' : $this->subject;		

		// Тело письма.
		$body = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru" lang="ru">
			<head>
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
				<style type="text/css">
					body { margin: 0 0 0 0; padding: 10px 10px 10px 10px; background: #ffffff; color: #000000; font-size: 14px; font-family: Tahoma, Arial, sans-serif; line-height: 1.4em; }		
					a { color: #003399; text-decoration: underline; font-size: 14px; font-family: Tahoma, Arial, sans-serif; line-height: 20px; }
					p { margin: 0 0 5px 0; padding: 0 0 0 0; color: #000000; font-size: 14px; font-family: Tahoma, Arial, sans-serif; line-height: 18px; }
					td { padding: 5px; }
					th { padding: 5px; }
					h1 { margin: 0 0 8px 0; padding: 0 0 0 0; color: #000000; font-size: 20px; font-family: Tahoma, Arial, sans-serif; line-height: 20px; font-weight: bold; }
					h2 { margin: 0 0 8px 0; padding: 0 0 0 0; color: #000000; font-size: 18px; font-family: Tahoma, Arial, sans-serif; line-height: 20px; font-weight: bold; }
					h3 { margin: 0 0 8px 0; padding: 0 0 0 0; color: #000000; font-size: 16px; font-family: Tahoma, Arial, sans-serif; line-height: 20px; font-weight: bold; }
					h4 { margin: 0 0 8px 0; padding: 0 0 0 0; color: #000000; font-size: 14px; font-family: Tahoma, Arial, sans-serif; line-height: 20px; font-weight: bold; }
				</style>
			</head>
			<body>
				' . $this->body . '
			</body>
		</html>';

		if (empty($this->files)) {
			$headers = array(
				'Content-Type: text/html; charset=UTF-8',
				'Content-Transfer-Encoding: BASE64',
				'MIME-Version: 1.0',
				'From: ' . $this->from,
				'Date: ' . date('r')
			);
		} else {
			$boundary = '--' . md5(uniqid(time()));
			$headers = array(
				'Content-Type: multipart/mixed; boundary="' . $boundary . '"',
				'From: ' . $this->from,
				'--' . $boundary,
				'Content-Type: text/html; charset=UTF-8',
				'Content-Transfer-Encoding: base64',
				"\r\n",
				chunk_split(base64_encode($this->body)),
			);

			foreach ($this->files as $row) {
				if (is_file($row)) {
					$name = basename($row);
					$fp = fopen($row, 'rb');  
					$file = fread($fp, filesize($row));   
					fclose($fp); 			

					$headers[] =  "\r\n--" . $boundary;   
					$headers[] = 'Content-Type: application/octet-stream; name="' . $name . '"';   
					$headers[] = 'Content-Transfer-Encoding: base64';   
					$headers[] = 'Content-Disposition: attachment; filename="' . $name . '"';   
					$headers[] = "\r\n"; 
					$headers[] = chunk_split(base64_encode($file)); 					
				}
			}
			
			$headers[] = "\r\n--" . $boundary . '--';  
		}

		foreach ($this->to as $to) {
			// Дамп письма в файл.
			if ($this->dump == true) {
				$headers_dump = array(
					'To: ' . $to,
					'Subject: ' . $subject
				);

				$headers_dump = array_merge($headers_dump, $headers);
				$headers_dump[] = '';
				$headers_dump[] = base64_encode($this->body);

				$file = $this->safeFile(rtrim($this->dump_path, '/') . '/' . date('Y-m-d_H-i-s') . '.eml');
				file_put_contents($file, implode("\r\n", $headers_dump));
			}

			mb_send_mail($to, $subject, $this->body, implode("\r\n", $headers));	
		}

		return true;
	}
}