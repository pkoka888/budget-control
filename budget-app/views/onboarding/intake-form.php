<?php
// Comprehensive User Intake Form for AI-Powered Personal Assistant
// This form captures all necessary information for Month 1 implementation
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Personal Assistant - Welcome</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .step-indicator { @apply flex items-center justify-center w-8 h-8 rounded-full border-2 text-sm font-semibold; }
        .step-active { @apply bg-blue-600 border-blue-600 text-white; }
        .step-completed { @apply bg-green-600 border-green-600 text-white; }
        .step-inactive { @apply border-gray-300 text-gray-300; }
        .form-section { @apply mb-8 p-6 bg-white rounded-lg shadow-sm border; }
        .progress-bar { @apply w-full bg-gray-200 rounded-full h-2; }
        .progress-fill { @apply bg-blue-600 h-2 rounded-full transition-all duration-300; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="max-w-4xl mx-auto py-8 px-4">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                <i class="fas fa-robot text-blue-600 mr-2"></i>
                AI Personal Assistant Setup
            </h1>
            <p class="text-gray-600">Let's get to know you better to provide personalized financial guidance</p>
        </div>

        <!-- Progress Bar -->
        <div class="mb-8">
            <div class="progress-bar">
                <div id="progressFill" class="progress-fill" style="width: 0%"></div>
            </div>
            <div class="flex justify-between mt-2">
                <span class="text-sm text-gray-600">Household</span>
                <span class="text-sm text-gray-600">Financial</span>
                <span class="text-sm text-gray-600">Goals</span>
                <span class="text-sm text-gray-600">Technical</span>
            </div>
        </div>

        <!-- Step Indicators -->
        <div class="flex justify-center mb-8">
            <div class="flex items-center space-x-4">
                <div id="step1" class="step-indicator step-active">1</div>
                <div class="w-8 h-0.5 bg-gray-300"></div>
                <div id="step2" class="step-indicator step-inactive">2</div>
                <div class="w-8 h-0.5 bg-gray-300"></div>
                <div id="step3" class="step-indicator step-inactive">3</div>
                <div class="w-8 h-0.5 bg-gray-300"></div>
                <div id="step4" class="step-indicator step-inactive">4</div>
            </div>
        </div>

        <form id="intakeForm" method="POST" action="/api/onboarding/submit">
            <!-- Step 1: Household Details -->
            <div id="step1Content" class="form-section">
                <h2 class="text-xl font-semibold mb-4 text-gray-800">
                    <i class="fas fa-home text-blue-600 mr-2"></i>
                    Household Information
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Personal Information -->
                    <div class="space-y-4">
                        <h3 class="font-medium text-gray-700">Personal Details</h3>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                            <input type="text" name="personal[name]" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth *</label>
                            <input type="date" name="personal[dob]" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                            <select name="personal[gender]"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="non-binary">Non-binary</option>
                                <option value="prefer-not-to-say">Prefer not to say</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Marital Status</label>
                            <select name="personal[marital_status]"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select status</option>
                                <option value="single">Single</option>
                                <option value="married">Married</option>
                                <option value="divorced">Divorced</option>
                                <option value="widowed">Widowed</option>
                                <option value="domestic_partnership">Domestic Partnership</option>
                            </select>
                        </div>
                    </div>

                    <!-- Household Composition -->
                    <div class="space-y-4">
                        <h3 class="font-medium text-gray-700">Household</h3>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Number of Dependents</label>
                            <input type="number" name="household[dependents]" min="0" max="10"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Household Size</label>
                            <input type="number" name="household[size]" min="1" max="20"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Primary Residence Type</label>
                            <select name="household[residence_type]"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select type</option>
                                <option value="owned">Owned</option>
                                <option value="rented">Rented</option>
                                <option value="mortgaged">Mortgaged</option>
                                <option value="family">Living with family</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Location (City, Country) *</label>
                            <input type="text" name="household[location]" required
                                   placeholder="e.g., Prague, Czech Republic"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                </div>

                <!-- Financial Obligations -->
                <div class="mt-6">
                    <h3 class="font-medium text-gray-700 mb-3">Financial Obligations</h3>
                    <div class="space-y-3">
                        <div class="flex items-center">
                            <input type="checkbox" name="obligations[mortgage]" value="1" id="mortgage"
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="mortgage" class="ml-2 text-sm text-gray-700">Mortgage/Rent Payment</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" name="obligations[car_loan]" value="1" id="car_loan"
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="car_loan" class="ml-2 text-sm text-gray-700">Car Loan</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" name="obligations[student_loan]" value="1" id="student_loan"
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="student_loan" class="ml-2 text-sm text-gray-700">Student Loan</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" name="obligations[credit_cards]" value="1" id="credit_cards"
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="credit_cards" class="ml-2 text-sm text-gray-700">Credit Card Debt</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" name="obligations[other_debt]" value="1" id="other_debt"
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="other_debt" class="ml-2 text-sm text-gray-700">Other Debt</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 2: Job & Income Profile -->
            <div id="step2Content" class="form-section hidden">
                <h2 class="text-xl font-semibold mb-4 text-gray-800">
                    <i class="fas fa-briefcase text-blue-600 mr-2"></i>
                    Employment & Income
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Employment Details -->
                    <div class="space-y-4">
                        <h3 class="font-medium text-gray-700">Employment Information</h3>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Employment Status *</label>
                            <select name="employment[status]" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select status</option>
                                <option value="full_time">Full-time employed</option>
                                <option value="part_time">Part-time employed</option>
                                <option value="self_employed">Self-employed</option>
                                <option value="freelance">Freelance/Contract</option>
                                <option value="unemployed">Unemployed</option>
                                <option value="student">Student</option>
                                <option value="retired">Retired</option>
                                <option value="homemaker">Homemaker</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Job Title/Industry</label>
                            <input type="text" name="employment[job_title]"
                                   placeholder="e.g., Software Engineer, Healthcare"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Years of Experience</label>
                            <input type="number" name="employment[experience_years]" min="0" max="50"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Work Remote?</label>
                            <select name="employment[remote_work]"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select option</option>
                                <option value="full_remote">Fully remote</option>
                                <option value="hybrid">Hybrid</option>
                                <option value="office">Office-based</option>
                                <option value="flexible">Flexible</option>
                            </select>
                        </div>
                    </div>

                    <!-- Income Information -->
                    <div class="space-y-4">
                        <h3 class="font-medium text-gray-700">Income Details</h3>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Monthly Gross Income (CZK) *</label>
                            <input type="number" name="income[monthly_gross]" min="0" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Monthly Net Income (CZK) *</label>
                            <input type="number" name="income[monthly_net]" min="0" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Income Frequency</label>
                            <select name="income[frequency]"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="monthly">Monthly</option>
                                <option value="bi-weekly">Bi-weekly</option>
                                <option value="weekly">Weekly</option>
                                <option value="irregular">Irregular</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Additional Income Sources</label>
                            <div class="space-y-2">
                                <div class="flex items-center">
                                    <input type="checkbox" name="income[sources][]" value="investments" id="investments"
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="investments" class="ml-2 text-sm text-gray-700">Investments/Dividends</label>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" name="income[sources][]" value="rental" id="rental"
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="rental" class="ml-2 text-sm text-gray-700">Rental Income</label>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" name="income[sources][]" value="freelance" id="freelance_income"
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="freelance_income" class="ml-2 text-sm text-gray-700">Freelance/Side Hustle</label>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" name="income[sources][]" value="other" id="other_income"
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="other_income" class="ml-2 text-sm text-gray-700">Other</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Relocation Plans -->
                <div class="mt-6">
                    <h3 class="font-medium text-gray-700 mb-3">Relocation Plans</h3>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Considering relocation in next 2 years?</label>
                            <select name="relocation[considering]"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="no">No</option>
                                <option value="maybe">Maybe</option>
                                <option value="yes">Yes</option>
                                <option value="actively_planning">Actively planning</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Target Locations (if any)</label>
                            <input type="text" name="relocation[targets]"
                                   placeholder="e.g., Berlin, Barcelona, Prague suburbs"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 3: Financial Goals & Risk -->
            <div id="step3Content" class="form-section hidden">
                <h2 class="text-xl font-semibold mb-4 text-gray-800">
                    <i class="fas fa-bullseye text-blue-600 mr-2"></i>
                    Financial Goals & Preferences
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Financial Goals -->
                    <div class="space-y-4">
                        <h3 class="font-medium text-gray-700">Primary Goals</h3>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">What are your main financial goals? (Select all that apply)</label>
                            <div class="space-y-2">
                                <div class="flex items-center">
                                    <input type="checkbox" name="goals[primary][]" value="emergency_fund" id="emergency_fund"
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="emergency_fund" class="ml-2 text-sm text-gray-700">Build emergency fund</label>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" name="goals[primary][]" value="debt_payoff" id="debt_payoff"
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="debt_payoff" class="ml-2 text-sm text-gray-700">Pay off debt</label>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" name="goals[primary][]" value="home_purchase" id="home_purchase"
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="home_purchase" class="ml-2 text-sm text-gray-700">Save for home purchase</label>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" name="goals[primary][]" value="retirement" id="retirement"
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="retirement" class="ml-2 text-sm text-gray-700">Retirement savings</label>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" name="goals[primary][]" value="investment" id="investment"
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="investment" class="ml-2 text-sm text-gray-700">Investment growth</label>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" name="goals[primary][]" value="education" id="education"
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="education" class="ml-2 text-sm text-gray-700">Education funding</label>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" name="goals[primary][]" value="vacation" id="vacation"
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="vacation" class="ml-2 text-sm text-gray-700">Travel/Vacation fund</label>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Time Horizon for Goals</label>
                            <select name="goals[time_horizon]"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select timeline</option>
                                <option value="short_term">0-2 years</option>
                                <option value="medium_term">2-5 years</option>
                                <option value="long_term">5-10 years</option>
                                <option value="very_long_term">10+ years</option>
                            </select>
                        </div>
                    </div>

                    <!-- Risk Tolerance & Preferences -->
                    <div class="space-y-4">
                        <h3 class="font-medium text-gray-700">Risk Tolerance</h3>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Investment Risk Tolerance *</label>
                            <select name="risk[tolerance]" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select tolerance</option>
                                <option value="very_conservative">Very Conservative (Preserve capital)</option>
                                <option value="conservative">Conservative (Some growth, low risk)</option>
                                <option value="moderate">Moderate (Balanced growth & safety)</option>
                                <option value="aggressive">Aggressive (High growth potential)</option>
                                <option value="very_aggressive">Very Aggressive (Maximum growth)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Investment Experience</label>
                            <select name="risk[experience]"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select experience</option>
                                <option value="none">No experience</option>
                                <option value="beginner">Beginner (Some savings accounts)</option>
                                <option value="intermediate">Intermediate (Stocks, bonds, mutual funds)</option>
                                <option value="advanced">Advanced (Options, derivatives, etc.)</option>
                                <option value="expert">Expert (Active trader/investor)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Urgency Level</label>
                            <select name="goals[urgency]"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select urgency</option>
                                <option value="low">Low - I have plenty of time</option>
                                <option value="medium">Medium - Some time pressure</option>
                                <option value="high">High - Need results soon</option>
                                <option value="critical">Critical - Immediate action needed</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Monthly Savings Capacity (CZK)</label>
                            <input type="number" name="goals[monthly_savings]" min="0"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 4: Technical Skills & Preferences -->
            <div id="step4Content" class="form-section hidden">
                <h2 class="text-xl font-semibold mb-4 text-gray-800">
                    <i class="fas fa-cogs text-blue-600 mr-2"></i>
                    Technical Skills & Communication
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- AI & Technical Skills -->
                    <div class="space-y-4">
                        <h3 class="font-medium text-gray-700">AI & Technology Comfort</h3>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">AI Experience Level *</label>
                            <select name="technical[ai_experience]" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select experience</option>
                                <option value="beginner">Beginner (New to AI)</option>
                                <option value="intermediate">Intermediate (Used ChatGPT, etc.)</option>
                                <option value="advanced">Advanced (Used AI for work/school)</option>
                                <option value="expert">Expert (Built AI systems or models)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Preferred AI Interaction Style</label>
                            <select name="technical[ai_style]"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select style</option>
                                <option value="conservative">Conservative (Explain everything step-by-step)</option>
                                <option value="balanced">Balanced (Some explanation, some autonomy)</option>
                                <option value="aggressive">Aggressive (Take initiative, act autonomously)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Technology Comfort Level</label>
                            <select name="technical[tech_comfort]"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select comfort level</option>
                                <option value="basic">Basic (Email, social media)</option>
                                <option value="intermediate">Intermediate (Spreadsheets, online banking)</option>
                                <option value="advanced">Advanced (Programming, data analysis)</option>
                                <option value="expert">Expert (System administration, development)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tools/Platforms You Use</label>
                            <div class="space-y-2">
                                <div class="flex items-center">
                                    <input type="checkbox" name="technical[tools][]" value="excel" id="excel"
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="excel" class="ml-2 text-sm text-gray-700">Excel/Google Sheets</label>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" name="technical[tools][]" value="quickbooks" id="quickbooks"
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="quickbooks" class="ml-2 text-sm text-gray-700">QuickBooks/Accounting Software</label>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" name="technical[tools][]" value="banking_apps" id="banking_apps"
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="banking_apps" class="ml-2 text-sm text-gray-700">Banking Apps</label>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" name="technical[tools][]" value="investment_apps" id="investment_apps"
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="investment_apps" class="ml-2 text-sm text-gray-700">Investment Apps</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Language & Communication -->
                    <div class="space-y-4">
                        <h3 class="font-medium text-gray-700">Language & Communication</h3>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Preferred Language *</label>
                            <select name="communication[language]" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select language</option>
                                <option value="english">English</option>
                                <option value="czech">Czech</option>
                                <option value="slovak">Slovak</option>
                                <option value="german">German</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">English Proficiency</label>
                            <select name="communication[english_level]"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select level</option>
                                <option value="native">Native/Bilingual</option>
                                <option value="fluent">Fluent</option>
                                <option value="advanced">Advanced</option>
                                <option value="intermediate">Intermediate</option>
                                <option value="beginner">Beginner</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Communication Style Preference</label>
                            <select name="communication[style]"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select style</option>
                                <option value="formal">Formal/Professional</option>
                                <option value="casual">Casual/Friendly</option>
                                <option value="technical">Technical/Detailed</option>
                                <option value="simple">Simple/Clear</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Notification Preferences</label>
                            <div class="space-y-2">
                                <div class="flex items-center">
                                    <input type="checkbox" name="communication[notifications][]" value="email" id="email_notif"
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="email_notif" class="ml-2 text-sm text-gray-700">Email</label>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" name="communication[notifications][]" value="sms" id="sms_notif"
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="sms_notif" class="ml-2 text-sm text-gray-700">SMS/Text</label>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" name="communication[notifications][]" value="app" id="app_notif"
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="app_notif" class="ml-2 text-sm text-gray-700">In-App Notifications</label>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Additional Comments</label>
                            <textarea name="communication[comments]" rows="3"
                                      placeholder="Any specific preferences or requirements for working with your AI assistant..."
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="flex justify-between mt-8">
                <button type="button" id="prevBtn"
                        class="px-6 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 hidden">
                    <i class="fas fa-arrow-left mr-2"></i>Previous
                </button>

                <button type="button" id="nextBtn"
                        class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    Next<i class="fas fa-arrow-right ml-2"></i>
                </button>

                <button type="submit" id="submitBtn"
                        class="px-6 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 hidden">
                    <i class="fas fa-check mr-2"></i>Complete Setup
                </button>
            </div>
        </form>
    </div>

    <script>
        // Form navigation and validation logic
        let currentStep = 1;
        const totalSteps = 4;

        function updateUI() {
            // Hide all steps
            for (let i = 1; i <= totalSteps; i++) {
                document.getElementById(`step${i}Content`).classList.add('hidden');
                document.getElementById(`step${i}`).classList.remove('step-active', 'step-completed');
                document.getElementById(`step${i}`).classList.add('step-inactive');
            }

            // Show current step
            document.getElementById(`step${currentStep}Content`).classList.remove('hidden');
            document.getElementById(`step${currentStep}`).classList.add('step-active');
            document.getElementById(`step${currentStep}`).classList.remove('step-inactive');

            // Mark previous steps as completed
            for (let i = 1; i < currentStep; i++) {
                document.getElementById(`step${i}`).classList.add('step-completed');
                document.getElementById(`step${i}`).classList.remove('step-inactive');
            }

            // Update progress bar
            const progress = ((currentStep - 1) / (totalSteps - 1)) * 100;
            document.getElementById('progressFill').style.width = progress + '%';

            // Update buttons
            document.getElementById('prevBtn').classList.toggle('hidden', currentStep === 1);
            document.getElementById('nextBtn').classList.toggle('hidden', currentStep === totalSteps);
            document.getElementById('submitBtn').classList.toggle('hidden', currentStep !== totalSteps);
        }

        function validateCurrentStep() {
            const stepContent = document.getElementById(`step${currentStep}Content`);
            const requiredFields = stepContent.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('border-red-500');
                    isValid = false;
                } else {
                    field.classList.remove('border-red-500');
                }
            });

            return isValid;
        }

        // Event listeners
        document.getElementById('nextBtn').addEventListener('click', () => {
            if (validateCurrentStep()) {
                if (currentStep < totalSteps) {
                    currentStep++;
                    updateUI();
                }
            } else {
                alert('Please fill in all required fields before proceeding.');
            }
        });

        document.getElementById('prevBtn').addEventListener('click', () => {
            if (currentStep > 1) {
                currentStep--;
                updateUI();
            }
        });

        document.getElementById('intakeForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            if (!validateCurrentStep()) {
                alert('Please fill in all required fields.');
                return;
            }

            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());

            // Convert checkbox arrays
            const checkboxFields = ['obligations', 'income[sources]', 'goals[primary]', 'technical[tools]', 'communication[notifications]'];
            checkboxFields.forEach(field => {
                const values = formData.getAll(field + '[]');
                if (values.length > 0) {
                    data[field] = values;
                }
            });

            try {
                const response = await fetch('/api/onboarding/submit', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });

                if (response.ok) {
                    window.location.href = '/dashboard';
                } else {
                    alert('Error submitting form. Please try again.');
                }
            } catch (error) {
                alert('Network error. Please check your connection and try again.');
            }
        });

        // Initialize
        updateUI();
    </script>
</body>
</html>