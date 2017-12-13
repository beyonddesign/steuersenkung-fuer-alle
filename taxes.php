<?php

// get submitted input from user
$net_income = isset($_POST['net_income']) ? filter_var(trim($_POST['net_income']), FILTER_SANITIZE_NUMBER_INT) : 0;
$civil_status = isset($_POST['civil_status']) ? filter_var(trim($_POST['civil_status']), FILTER_SANITIZE_STRING) : '';
$children = isset($_POST['children']) ? filter_var(trim($_POST['children']), FILTER_SANITIZE_EMAIL) : 0;
$children_expenses = isset($_POST['children_expenses']) ? filter_var(trim($_POST['children_expenses']), FILTER_SANITIZE_NUMBER_INT) : 0;
$retirement_plan = isset($_POST['retirement_plan']) ? filter_var(trim($_POST['retirement_plan']), FILTER_SANITIZE_NUMBER_INT) : 0;
$city = isset($_POST['city']) ? filter_var(trim($_POST['city']), FILTER_SANITIZE_STRING) : '';

// define constants
define('TAX_RATE_RIEHEN', 0.9);
define('TAX_RATE_BETTINGEN', 0.89);
define('MAX_RETIREMENT_PLAN', 6768);
define('INCOME_THRESHOLD', 200000);
define('CURRENT_RATE', 0.2225);
define('OTHER_RATE', 0.2125);
define('THRESHOLD_RATE', 0.26);
define('MAX_DEDUCTION_PER_CHILD', 10000);
define('DEDUCTION_PER_CHILD', 7800);
define('SP_TAXABLE_DEDUCTION', 2000);
define('CONST_DEDUCTION', 24000);

// determine taxable income
if ($civil_status == 'married') {
  // calculate taxable income for a family
  if ($retirement_plan > 2*MAX_RETIREMENT_PLAN) $retirement_plan = 2*MAX_RETIREMENT_PLAN;
  if ($children_expenses > $children*MAX_DEDUCTION_PER_CHILD) $children_expenses = $children*MAX_DEDUCTION_PER_CHILD;
  $taxable_income = $net_income - 2*CONST_DEDUCTION - $retirement_plan - $children*DEDUCTION_PER_CHILD - $children_expenses;
  $taxable_income_sp = $taxable_income - 2*SP_TAXABLE_DEDUCTION;
} else if ($civil_status = 'divorced') {
  // calculate taxable income for single parent 
  if ($retirement_plan > MAX_RETIREMENT_PLAN) $retirement_plan = MAX_RETIREMENT_PLAN;
  if ($children_expenses > $children*MAX_DEDUCTION_PER_CHILD) $children_expenses = $children*MAX_DEDUCTION_PER_CHILD;
  $taxable_income = $net_income - CONST_DEDUCTION - $retirement_plan - $children*DEDUCTION_PER_CHILD - $children_expenses - 12000;
  $taxable_income_sp = $taxable_income - SP_TAXABLE_DEDUCTION;
} else {
  // calculate taxable income for single person (no deductions made from children expensions)
  if ($retirement_plan > MAX_RETIREMENT_PLAN) $retirement_plan = MAX_RETIREMENT_PLAN;
  $taxable_income = $net_income - CONST_DEDUCTION - $retirement_plan;
  $taxable_income_sp = $taxable_income - SP_TAXABLE_DEDUCTION;
}

// make sure taxable income can't be lower than zero
if ($taxable_income < 0) $taxable_income = 0;
if ($taxable_income_sp < 0) $taxable_income_sp = 0;

// calculate taxes to be paid
$income_threshold = INCOME_THRESHOLD;
if ($civil_status == 'married') $income_threshold *= 2;
if ($taxable_income <= $income_threshold) {
  // taxable income is below threshold
  $taxes_current = CURRENT_RATE*$taxable_income;
  $taxes_sp = CURRENT_RATE*$taxable_income_sp;
  $taxes_other = OTHER_RATE*$taxable_income;
} else {
  // taxable income is over threshold
  $taxes_current = CURRENT_RATE*$income_threshold + ($taxable_income-$income_threshold)*THRESHOLD_RATE;
  $taxes_sp = CURRENT_RATE*$income_threshold + ($taxable_income_sp-$income_threshold)*THRESHOLD_RATE;
  $taxes_other = OTHER_RATE*$income_threshold + ($taxable_income-$income_threshold)*THRESHOLD_RATE;
}

// adjust the tax rate based on the selected city
$tax_rate = 1;
if ($city == 'riehen') {
  $tax_rate = TAX_RATE_RIEHEN;
} else if ($city == 'bettingen') {
  $tax_rate = TAX_RATE_BETTINGEN;
}
$taxes_current *= $tax_rate;
$taxes_sp *= $tax_rate;
$taxes_other *= $tax_rate;

// prepare output result array
$result = [
    'sp' => [
      'tax' => round($taxes_sp),
      'savings' => round($taxes_sp - $taxes_current)
    ],
    'other' => [
      'tax' => round($taxes_other),
      'savings' => round($taxes_other - $taxes_current)
    ],
];

// return JSON result to the user
header('Content-Type: application/json');
echo json_encode($result);
?>
