<?php
class Table {
	public function generate($data, $options = array()) {
		if (!is_array($data)) {
			return false;
		}

		$keys = (is_object($data[0]) && is_subclass_of($data[0], 'Model'))
			? array_keys($data[0]->_metadata) 
			: array_keys($data[0]);

		$class = !isset($options['class']) ? ' class="' . $options['class'] . '"' : '';

		$table  = '<table width="100%"' . $class . '><tr>';
		foreach ($keys as $key) {
			if (is_array($options['exclude']) && in_array($key, $options['exclude'])) {
				continue;
			}
			if (!is_array($options['include']) 
			|| (is_array($options['include']) && in_array($key, $options['include']))) {
				$table .= '<th>' . ucwords(str_replace('_', ' ', $key)) . '</th>';
			}
		}
		if (is_array($options['actions'])) {
			$table .= '<th>Actions</th>';
		}
		$table .= "</tr>\n";
		foreach ($data as $item) {
			$table .= '<tr>';
			foreach ($keys as $key) {
				if (is_array($options['exclude']) && in_array($key, $options['exclude'])) {
					continue;
				}
				if (!is_array($options['include']) 
				|| (is_array($options['include']) && in_array($key, $options['include']))) {
					$table .= '<td class="cell_' . $key . '">';
					if (is_array($options['format']) && array_key_exists($key, $options['format'])) {
						$code = str_replace('[field]', (is_object($item) ? $item->{$key} : $item[$key]),
							$options['format'][$key]);
						eval('$table .= ' . $code);
					} else {
						$table .= (is_object($item) ? $item->{$key} : $item[$key]);
					}
					$table .= '</td>';
				}
			}
			if (is_array($options['actions'])) {
				$table .= '<td class="actions">';
				foreach ($options['actions'] as $name => $action) {
					if (is_array($action['replace']) && (isset($action['url']) || isset($action['click']))) {
						foreach ($action['replace'] as $key) {
							if (isset($action['url'])) {
								$action['url'] = str_replace('[' . $key . ']',
									(is_object($item) ? $item->{$key} : $item[$key]), $action['url']);
							}
							if (isset($action['click'])) {
								$action['click'] = str_replace('[' . $key . ']',
									(is_object($item) ? $item->{$key} : $item[$key]), $action['click']);
							}
						}
					}
					$url = isset($action['url']) ? $action['url'] : '#';
					$class = isset($action['class']) ? ' class="' . $action['class'] . '"' : '';
					$click = isset($action['click']) ? ' onclick="' . $action['click'] . '"' : ''; 
					$table .= '<a href="' . $url . '"' . $class . $click . '>';
					$table .= isset($action['img'])
						? '<img src="' . $action['img'] . '" alt="' . ucwords($name) . '" />'
						: ucwords($name);
					$table .= '</a>';
				}
				$table .= '</td>';
			}
			$table .= "</tr>\n";
		}
		$table .= '</table>';
		return $table;
	}
}		
