<?php

/**
 * Class Xhgui_Controller_Xml
 */
class Xhgui_Controller_Xml extends Xhgui_Controller
{

	public function __construct($app, $profiles, $watches)
	{
		$this->_app = $app;
		$this->_profiles = $profiles;
		$this->_watches = $watches;
	}

	public function index()
	{
		$request = $this->_app->request();

		$search = array();
		$keys = array('date_start', 'date_end', 'url');
		foreach ($keys as $key) {
			if ($request->get($key)) {
				$search[$key] = $request->get($key);
			}
		}
		$sort = $request->get('sort');

		$result = $this->_profiles->getAll(array(
			'sort' => $sort,
			'page' => $request->get('page'),
			'direction' => $request->get('direction'),
			'perPage' => $this->_app->config('page.limit'),
			'conditions' => $search,
			'projection' => true,
		));

		$title = 'Recent runs';
		$titleMap = array(
			'wt' => 'Longest wall time',
			'cpu' => 'Most CPU time',
			'mu' => 'Highest memory use',
		);
		if (isset($titleMap[$sort])) {
			$title = $titleMap[$sort];
		}

		$paging = array(
			'total_pages' => $result['totalPages'],
			'page' => $result['page'],
			'sort' => $sort,
			'direction' => $result['direction']
		);

		$this->_template = 'JUnit/xml.twig';
		$this->set(array(
			'paging' => $paging,
			'base_url' => 'home',
			'runs' => $result['results'],
			'date_format' => $this->_app->config('date.format'),
			'search' => $search,
			'has_search' => strlen(implode('', $search)) > 0,
			'title' => $title
		));
	}

	public function view()
	{
		$request = $this->_app->request();
		$detailCount = $this->_app->config('detail.count');
		$result = $this->_profiles->get($request->get('id'));

		$result->calculateSelf();

		// Self wall time graph
		$timeChart = $result->extractDimension('ewt', $detailCount);

		// Memory Block
		$memoryChart = $result->extractDimension('emu', $detailCount);

		// Watched Functions Block
		$watchedFunctions = array();
		foreach ($this->_watches->getAll() as $watch) {
			$matches = $result->getWatched($watch['name']);
			if ($matches) {
				$watchedFunctions = array_merge($watchedFunctions, $matches);
			}
		}

		$profile = $result->sort('ewt', $result->getProfile());

		$this->_template = 'runs/view.twig';
		$this->set(array(
			'profile' => $profile,
			'result' => $result,
			'wall_time' => $timeChart,
			'memory' => $memoryChart,
			'watches' => $watchedFunctions,
			'date_format' => $this->_app->config('date.format'),
		));
	}
} 