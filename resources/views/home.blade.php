<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>AuthHub - OAuth 2.0 Authorization Server</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    
    <!-- Custom Styles -->
    <style>
        :root {
            --laravel-red: #ef4444;
            --laravel-red-dark: #dc2626;
            --laravel-orange: #f97316;
            --primary-bg: #ffffff;
            --secondary-bg: #f8fafc;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --border-color: #e5e7eb;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: var(--text-primary);
            background-color: var(--primary-bg);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header Styles */
        .header {
            background-color: var(--primary-bg);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--laravel-red);
            text-decoration: none;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        .nav-menu a {
            text-decoration: none;
            color: var(--text-secondary);
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .nav-menu a:hover {
            color: var(--laravel-red);
        }

        /* Main Content */
        .main-content {
            margin-top: 80px;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 6rem 0;
            text-align: center;
        }

        .hero h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .hero p {
            font-size: 1.25rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        .cta-button {
            display: inline-block;
            background-color: var(--laravel-red);
            color: white;
            padding: 1rem 2rem;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: background-color 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .cta-button:hover {
            background-color: var(--laravel-red-dark);
        }

        /* Goals Section */
        .goals {
            padding: 5rem 0;
            background-color: var(--secondary-bg);
        }

        .goals h2 {
            text-align: center;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 3rem;
            color: var(--text-primary);
        }

        .goals-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .goal-item {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .goal-item:hover {
            transform: translateY(-5px);
        }

        .goal-item h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--laravel-red);
        }

        .goal-item p {
            color: var(--text-secondary);
        }

        .goals-cta {
            text-align: center;
        }

        /* Form Section */
        .form-section {
            padding: 5rem 0;
        }

        .form-section h2 {
            text-align: center;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 3rem;
            color: var(--text-primary);
        }

        .form-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--laravel-red);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .toggle-group {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .toggle-option {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .toggle-option input[type="radio"] {
            width: auto;
        }

        .hidden {
            display: none;
        }

        /* Footer */
        .footer {
            background-color: var(--text-primary);
            color: white;
            padding: 3rem 0;
            text-align: center;
        }

        .social-links {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .social-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .social-links a:hover {
            color: var(--laravel-red);
        }

        .footer-text {
            opacity: 0.8;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-menu {
                gap: 1rem;
            }

            .hero h1 {
                font-size: 2.5rem;
            }

            .hero p {
                font-size: 1rem;
            }

            .goals-grid {
                grid-template-columns: 1fr;
            }

            .container {
                padding: 0 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <a href="#" class="logo">AuthHub</a>
                <nav>
                    <ul class="nav-menu">
                        <li><a href="#hero">Home</a></li>
                        <li><a href="#goals">Goals</a></li>
                        <li><a href="#join">Join Us</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Hero Section -->
        <section id="hero" class="hero">
            <div class="container">
                <h1>AuthHub</h1>
                <p>A modern, secure OAuth 2.0 Authorization Server built with Laravel</p>
                <a href="#goals" class="cta-button">Know More</a>
            </div>
        </section>

        <!-- Goals Section -->
        <section id="goals" class="goals">
            <div class="container">
                <h2>Project Goals</h2>
                <div class="goals-grid">
                    <div class="goal-item">
                        <h3>🔐 Secure Authentication</h3>
                        <p>Implement industry-standard OAuth 2.0 protocols with robust security measures and best practices for user authentication and authorization.</p>
                    </div>
                    <div class="goal-item">
                        <h3>📚 Educational Resource</h3>
                        <p>Serve as a comprehensive learning platform for developers wanting to understand OAuth 2.0 implementation and Laravel development patterns.</p>
                    </div>
                    <div class="goal-item">
                        <h3>🚀 Modern Architecture</h3>
                        <p>Showcase modern Laravel features, clean code architecture, and scalable design patterns for enterprise-level applications.</p>
                    </div>
                    <div class="goal-item">
                        <h3>🌍 Open Collaboration</h3>
                        <p>Foster an open-source community where developers can contribute, learn, and grow together while building something meaningful.</p>
                    </div>
                </div>
                <div class="goals-cta">
                    <a href="#join" class="cta-button">Request to Join</a>
                </div>
            </div>
        </section>

        <!-- Join Request Form Section -->
        <section id="join" class="form-section">
            <div class="container">
                <h2>Join Our Project</h2>
                <div class="form-container">
                    @if(session('success'))
                        <div style="background-color: #d4edda; color: #155724; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem;">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if($errors->any())
                        <div style="background-color: #f8d7da; color: #721c24; padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem;">
                            <ul style="list-style: none; margin: 0; padding: 0;">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('join.request') }}">
                        @csrf
                        
                        <div class="form-group">
                            <label for="full_name">Full Name *</label>
                            <input type="text" id="full_name" name="full_name" value="{{ old('full_name') }}" required>
                        </div>

                        <div class="form-group">
                            <label>Do you have a GitHub account? *</label>
                            <div class="toggle-group">
                                <div class="toggle-option">
                                    <input type="radio" id="github_yes" name="has_github" value="yes" {{ old('has_github') === 'yes' ? 'checked' : '' }} required>
                                    <label for="github_yes">Yes</label>
                                </div>
                                <div class="toggle-option">
                                    <input type="radio" id="github_no" name="has_github" value="no" {{ old('has_github') === 'no' ? 'checked' : '' }} required>
                                    <label for="github_no">No</label>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="email">Email (must match your GitHub user if applicable) *</label>
                            <input type="email" id="email" name="email" value="{{ old('email') }}" required>
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone *</label>
                            <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" required>
                        </div>

                        <div class="form-group">
                            <label for="reason">Reason for joining *</label>
                            <select id="reason" name="reason" required>
                                <option value="">Select a reason...</option>
                                <option value="learn" {{ old('reason') === 'learn' ? 'selected' : '' }}>Learn PHP & Laravel</option>
                                <option value="improve" {{ old('reason') === 'improve' ? 'selected' : '' }}>Improve PHP & Laravel Skills</option>
                                <option value="collaborate" {{ old('reason') === 'collaborate' ? 'selected' : '' }}>I want to collaborate</option>
                                <option value="other" {{ old('reason') === 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>

                        <div class="form-group {{ old('reason') === 'other' ? '' : 'hidden' }}" id="other-reason-group">
                            <label for="other_reason">Please specify your reason *</label>
                            <input type="text" id="other_reason" name="other_reason" value="{{ old('other_reason') }}">
                        </div>

                        <div class="form-group">
                            <label for="message">Additional Message (Optional)</label>
                            <textarea id="message" name="message" placeholder="Tell us more about yourself, your experience, or what you'd like to contribute...">{{ old('message') }}</textarea>
                        </div>

                        <button type="submit" class="cta-button" style="width: 100%;">Submit Request</button>
                    </form>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="social-links">
                <a href="https://github.com/dtavares86" target="_blank">GitHub</a>
                <a href="https://linkedin.com/in/david-tavares-86" target="_blank">LinkedIn</a>
            </div>
            <p class="footer-text">© 2025 AuthHub by David Tavares. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Handle "Other" reason dropdown
        document.getElementById('reason').addEventListener('change', function() {
            const otherGroup = document.getElementById('other-reason-group');
            const otherInput = document.getElementById('other_reason');
            
            if (this.value === 'other') {
                otherGroup.classList.remove('hidden');
                otherInput.required = true;
            } else {
                otherGroup.classList.add('hidden');
                otherInput.required = false;
                otherInput.value = '';
            }
        });

        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>