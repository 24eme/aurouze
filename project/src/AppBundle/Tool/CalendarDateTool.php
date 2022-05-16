<?php
namespace AppBundle\Tool;

class CalendarDateTool
{
	const MODE_DAY = 'JOUR';
	const MODE_WEEK = 'SEMAINE';
	const MODE_MONTH = 'MOIS';

	protected $date;
	protected $mode;
	protected $nb_day_in_week;
	
	public static $libellesMois = array(
			'janv.',
			'févr.',
			'mars',
			'avril',
			'mai',
			'juin',
			'juil.',
			'août',
			'sept.',
			'oct.',
			'nov.',
			'déc.',
	);
	public static $libellesJours = array(
			'lun.',
			'mar.',
			'mer.',
			'jeu.',
			'ven.',
			'sam.',
			'dim.',
	);
	public function __construct($date = null, $mode = self::MODE_JOUR, $extra = false)
	{
		$this->setDate($date);
		$this->mode = $mode;
		$this->nb_day_in_week = ($extra)? 6 : 5;
	}

	public function getDate()
	{
		return $this->date;
	}

	public function getMode()
	{
		return $this->mode;
	}

	public function getViewJs()
	{
		if($this->mode == self::MODE_DAY) {

			return "agendaDay";
		}

		if($this->mode == self::MODE_MONTH) {

			return "month";
		}

		return "agendaWeek";
	}

	public function setDate($date = null, $mode = null)
	{
		if (!$date) {
			$this->date = new \DateTime();
		} else {
			if (!($date instanceof \DateTime)) {
				$this->date = new \DateTime($date);
			} else {
				$this->date = clone $date;
			}
		}
	}

	public function getDateDebutJour($format = null)
	{
		$ds = clone $this->date;
		return ($format)? $ds->format($format) : $ds;
	}

	public function getDateFinJour($format = null)
	{
		$ds = clone $this->date;
		return ($format)? $ds->format($format) : $ds;
	}

	public function getDateDebutSemaine($format = null)
	{
		$ds = clone $this->date;
		$ds->modify('-'.($ds->format('N')-1).' day');
		return ($format)? $ds->format($format) : $ds;
	}

	public function getDateDebutMois($format = null)
	{
		$ds = clone $this->date;
		$ds->modify('first day of this month');
		return ($format)? $ds->format($format) : $ds;
	}

	public function getDateFinMois($format = null)
	{
		$ds = clone $this->date;
		$ds->modify('last day of this month');
		return ($format)? $ds->format($format) : $ds;
	}

	public function getDateFinSemaine($format = null)
	{
		$ds = clone $this->date;
		$ds->modify('+'.($this->nb_day_in_week - $ds->format('N')).' day');
		return ($format)? $ds->format($format) : $ds;
	}

	public function getJourSuivant($format = null)
	{
		$ds = clone $this->getDate();
		$ds->modify('+1 day');
		return ($format)? $ds->format($format) : $ds;
	}

	public function getJourPrecedent($format = null)
	{
		$ds = clone $this->getDate();
		$ds->modify('-1 day');
		return ($format)? $ds->format($format) : $ds;
	}

	public function getSemaineSuivante($format = null)
	{
		$ds = $this->getDateDebutSemaine();
		$ds->modify('+1 week');
		return ($format)? $ds->format($format) : $ds;
	}

	public function getSemainePrecedente($format = null)
	{
		$ds = $this->getDateDebutSemaine();
		$ds->modify('-1 week');
		return ($format)? $ds->format($format) : $ds;
	}

	public function getMoisPrecedent($format = null)
	{
		$ds = $this->getDateDebutMois();
		$ds->modify('-1 month');
		return ($format)? $ds->format($format) : $ds;
	}

	public function getMoisSuivant($format = null)
	{
		$ds = $this->getDateDebutMois();
		$ds->modify('+1 month');
		return ($format)? $ds->format($format) : $ds;
	}

	public function getAujourdhui($format = null)
	{
		$ds = new \DateTime();
		return ($format)? $ds->format($format) : $ds;
	}

	public function getDateDebut($format = null) {
		if($this->getMode() == self::MODE_DAY) {

			return $this->getDateDebutJour($format);
		}

		if($this->getMode() == self::MODE_MONTH) {

			return $this->getDateDebutMois($format);
		}

		return $this->getDateDebutSemaine($format);
	}

	public function getDateFin($format = null) {
		if($this->getMode() == self::MODE_DAY) {

			return $this->getDateFinJour($format);
		}

		if($this->getMode() == self::MODE_MONTH) {

			return $this->getDateFinMois($format);
		}

		return $this->getDateFinSemaine($format);
	}


	public function getPrecedent() {
		if($this->getMode() == self::MODE_DAY) {

			return $this->getJourPrecedent();
		}

		if($this->getMode() == self::MODE_MONTH) {

			return $this->getMoisPrecedent();
		}

		return $this->getSemainePrecedente();
	}

	public function getSuivant() {
		if($this->getMode() == self::MODE_DAY) {

			return $this->getJourSuivant();
		}

		if($this->getMode() == self::MODE_MONTH) {

			return $this->getMoisSuivant();
		}

		return $this->getSemaineSuivante();
	}

	public function getLibelle() {
		$formatter = new \IntlDateFormatter("fr_FR", \IntlDateFormatter::LONG, \IntlDateFormatter::LONG);

		if($this->getMode() == self::MODE_DAY) {
			$formatter->setPattern("EEEE d MMMM Y");

			return ucfirst($formatter->format($this->getDate()));
		}

		if($this->getMode() == self::MODE_MONTH) {

			$formatter->setPattern("MMMM Y");

			return ucfirst($formatter->format($this->getDateDebutMois()));
		}


		$formatter->setPattern("MMM");

		$firstMonth = $formatter->format($this->getDateDebutSemaine());
		$lastMonth = $formatter->format($this->getDateFinSemaine());

		if($firstMonth == $lastMonth) {
			$firstMonth = "";
		} else {
			$firstMonth = " ".$firstMonth;
		}

		return sprintf("%s%s au %s %s %s", $this->getDateDebutSemaine()->format('j'), $firstMonth, $this->getDateFinSemaine()->format("j"), $lastMonth, $this->getDateFinSemaine()->format("Y"));
	}

	public static function getShortLibelleMois($mois)
	{
		return self::$libellesMois[$mois - 1];
	}

	public static function getShortLibelleJour($jour)
	{
		return self::$libellesJours[$jour - 1];
	}
}