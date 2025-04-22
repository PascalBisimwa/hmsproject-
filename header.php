<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kibris Aydin Hospital</title>
        <link rel="website icon" type=png
        href="img/cancer2.png">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="header.css"> <!-- Lien vers le fichier CSS externe -->
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">
            <img src="img/logo.png" alt="Logo" height="50"> Kibris Aydin Hospital
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link active" href="#">Home</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownAbout" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        About
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navbarDropdownAbout">
                        <li><a class="dropdown-item" href="#">Our History</a></li>
                        <li><a class="dropdown-item" href="#">Our Team</a></li>
                        <li><a class="dropdown-item" href="#">Our Values</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownServices" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Services
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navbarDropdownServices">
                        <li><a class="dropdown-item" href="#">Cardiology</a></li>
                        <li><a class="dropdown-item" href="#">Ophthalmology</a></li>
                        <li><a class="dropdown-item" href="#">Medical Laboratory</a></li>
                    </ul>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownDepartments" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Departments
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navbarDropdownDepartments">
                        <li><a class="dropdown-item" href="#">Orthopedics</a></li>
                        <li><a class="dropdown-item" href="#">Medical Imaging</a></li>
                        <li><a class="dropdown-item" href="#">Emergency Medicine</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                     <a class="nav-link" href="#quick-contact">Contact</a>
                </li>

                <!-- Ajout du lien Login -->
                <li class="nav-item">
                    <a class="nav-link" href="login.php">Login</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link btn btn-primary" href="#" style="margin-left: 10px; color: white; background-color: #007bff; border-radius: 5px;">Appointment</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
    <!-- Conteneur pour le logo et le cadre -->
    <div class="container">
        <div class="logo-message-container">
            <img src="img/cancer2.png" alt="Logo Cancer du Sein">
            <div class="message-section">
                Breast cancer : Think about screening <a href="#" class="font-semibold text-white"><span class="absolute inset-0" aria-hidden="true"></span>Read more <span aria-hidden="true">&rarr;</span></a>
            </div>

        </div>
    </div>

    <div class="welcome-message">
    <h1>Welcome to Kibris Aydin Hospital</h1>
    <p>Your health is our priority. We provide the best medical services for you and your loved ones.</p>
</div>
</div>

    <!-- Section About Us -->
    <div class="about-section">
    <div class="text-center">
        <h1 class="about-title">About Us</h1>
                <p class="mt-4 text-gray-600">Welcome to Kibris Ilim Hospital, where compassion meets excellence in healthcare. 
                For 2024, we have been committed to delivering comprehensive medical care tailored to the needs of our community.
                Our state-of-the-art facility is designed to provide a healing environment that prioritizes your well-being and comfort.</p>
            </div>
            <div class="mx-auto mt-12 max-w-2xl sm:mt-16 lg:mt-20 lg:max-w-none">
                <div class="card-container">
                    <div class="card">
                        <h3 class="card-title">Modern Facilities</h3>
                        <p class="card-text">Equipped with the latest medical technology, our hospital is designed to deliver the highest standard of care in a safe and welcoming environment.</p>
                        <a href="#" class="card-link">Learn more →</a>
                    </div>
                    <div class="card">
                        <h3 class="card-title">Experienced Professionals</h3>
                        <p class="card-text">Our team of doctors, nurses, and specialists are leaders in their fields, committed to providing personalized care.</p>
                        <a href="#" class="card-link">Learn more →</a>
                    </div>
                    <div class="card">
                        <h3 class="card-title">Comprehensive Services</h3>
                        <p class="card-text">From preventive care to advanced treatments, we offer a full spectrum of medical services to address a wide range of health needs.</p>
                        <a href="#" class="card-link">Learn more →</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section Nos Services -->
    <div id="services" class="services-section">
        <div class="container">
            <div class="text-center">
                <h2 class="services-title">Our Services</h2>
            </div>
            <div class="services-grid">
                <div class="service-card">
                    <h3 class="service-title"><img src="img/coeur.png" alt="" style="width: 50px; height: 50px; vertical-align: middle; margin-right:2px;"> Cardiology</h3>
                    <p class="service-description">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec lorem maximus malesuada lorem maximus mauris.</p>
                </div>
                <div class="service-card">
                    <h3 class="service-title"><img src="img/oeil.png" alt="" style="width: 50px; height: 50px; vertical-align: middle; margin-right:2px;"> Ophthalmology</h3>
                    <p class="service-description">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec lorem maximus malesuada lorem maximus mauris.</p>
                </div>
                <div class="service-card">
                    <h3 class="service-title"><img src="img/labo.png" alt="" style="width: 50px; height: 50px; vertical-align: middle; margin-right:2px;"> Medical laboratory</h3>
                    <p class="service-description">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec lorem maximus malesuada lorem maximus mauris.</p>
                </div>
                <div class="service-card">
                    <h3 class="service-title"><img src="img/dent.png" alt="" style="width: 50px; height: 50px; vertical-align: middle; margin-right:2px;"> Dental care</h3>
                    <p class="service-description">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec lorem maximus malesuada.</p>
                </div>
                <div class="service-card">
                    <h3 class="service-title"><img src="img/lit1.png" alt="" style="width: 50px; height: 50px; vertical-align: middle; margin-right:2px;"> Surgery</h3>
                    <p class="service-description">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec lorem maximus malesuada.</p>
                </div>
                <div class="service-card">
                    <h3 class="service-title"><img src="img/ner1.png" alt="" style="width: 70px; height: 70px; vertical-align: middle; margin-right:2px;"> Neurology</h3>
                    <p class="service-description">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec lorem maximus malesuada.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Section Nos Départements -->
    <div id="departments" class="departments-section">
        <div class="container">
            <div class="text-center">
                <h2 class="departments-title">Our Departments</h2>
            </div>
            <div class="departments-horizontal">
                <div class="department-horizontal">
                    <img src="img/orthopedie.jpg" alt="Orthopedics">
                    <div>
                        <h3>Orthopedicsimg/</h3>
                        <a href="#">Learn more</a>
                    </div>
                </div>
                <div class="department-horizontal">
                    <img src="img/imagerie.jpg" alt="medical imaging">
                    <div>
                        <h3>Medical imaging</h3>
                        <a href="#">Learn more</a>
                    </div>
                </div>
                <div class="department-horizontal">
                    <img src="img/image1.png" alt="emergency medicine">
                    <div>
                        <h3>Emergency medicine</h3>
                        <a href="#">Learn more</a>
                    </div>
                </div>
                <div class="department-horizontal">
                    <img src="img/pediatrie.jpeg" alt="pediatrics">
                    <div>
                        <h3>Pediatrics</h3>
                        <a href="#">Learn more</a>
                    </div>
                </div>
                <div class="department-horizontal">
                    <img src="img/neonatologie.jpg" alt="general medicine">
                    <div>
                        <h3>General medicine</h3>
                        <a href="#">Learn more</a>
                    </div>
                </div>
                <div class="department-horizontal">
                    <img src="img/dent.jpg" alt="Dentistry">
                    <div>
                        <h3>Dentistry</h3>
                        <a href="#">En savoir plus</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

<section class="team-section">
    <div class="team-overlay"></div> <!-- Overlay pour l'effet transparent -->
    <h2 class="section-title">Our Doctors</h2>
    <div class="team-grid">
        <div class="team-card">
            <img src="img/team1.jpg" alt="Membre de l'équipe 1" class="team-photo">
            <h3 class="team-name">Dr. Jean Dupont</h3>
            <p class="team-role">Médecin Généraliste</p>
        </div>
        <div class="team-card">
            <img src="img/team2.avif" alt="Membre de l'équipe 2" class="team-photo">
            <h3 class="team-name">Dr. Mc Curie</h3>
            <p class="team-role">Chirurgien</p>
        </div>
        <div class="team-card">
            <img src="img/teams3.avif" alt="Membre de l'équipe 3" class="team-photo">
            <h3 class="team-name">Dr. Pauline Martine</h3>
            <p class="team-role">Dentiste</p>
        </div>
    </div>
</section>

    <section class="testimonials-section">
    <h2 class="section-title">Patients
Reviews</h2>
    <div class="testimonials-grid">
        <div class="testimonial-card">
            <p class="testimonial-text">"UAs of today, I had a cardiac mr. The hospital is sparkling clean, thank you very much Mr. Gökhan, who welcomed me, for being very helpful and helpful. Cüneyt and the staff in the mr unit were very humble and respectful. I was very pleased, thank you very much for everything.."</p>
            <p class="testimonial-author">- Jean Dupont</p>
        </div>
        <div class="testimonial-card">
            <p class="testimonial-text">"Hello, I am Hacı Mehmet Taşkesen. I am undergoing chemotherapy treatment at Neolife Medical Center. I am pleased with the approach, sincerity and self-sacrificing support of all the technical team serving during my treatment. I would like to express my gratitude to the Kibris Aydin hospital. Kind regards,"</p>
            <p class="testimonial-author">- Marie Curie</p>
        </div>
        <div class="testimonial-card">
            <p class="testimonial-text">"At the beginning of December 2022, with the guidance of our doctor in Eskisehir Acıbadem Hospital, we had a Pet CT shot for prostate at Neolife Medical Center. Even though we came from out of town, they picked us up from the train station and left us until the end of the job. Special thanks to Tuncay Bayram for his interest.!"</p>
            <p class="testimonial-author">- Paul Martin</p>

        </div>
    </div>
</section>

<section class="faq-section">
    <div class="faq-overlay"></div> <!-- Overlay pour l'effet transparent -->
    <h2 class="section-title">Faq</h2>
    <div class="faq-grid">
        <div class="faq-card">
            <h3 class="faq-question">Comment prendre rendez-vous ?</h3>
            <p class="faq-answer">Vous pouvez prendre rendez-vous en ligne ou en appelant notre service client.</p>
        </div>
        <div class="faq-card">
            <h3 class="faq-question">Quels sont vos horaires d'ouverture ?</h3>
            <p class="faq-answer">Nous sommes ouverts du lundi au vendredi de 8h à 19h.</p>
        </div>
        <div class="faq-card">
            <h3 class="faq-question">Acceptez-vous les assurances ?</h3>
            <p class="faq-answer">Oui, nous acceptons la plupart des assurances santé.</p>
        </div>
    </div>
</section>

</body>

   <footer class="footer">
    <div class="footer-container">
        <!-- Section About Us -->
        <div class="footer-section">
            <h3 class="footer-title">Your health first +</h3>
            <p class="footer-text">“Medical expertise at the service of your health.”</p>
            <div class="footer-logo">
                <img src="img/logo.png" alt="Hospital Logo">
                <span>Kibris Aydin Hospital</span>
            </div>
        </div>

        <!-- Section Contact Rapide -->
        <div class="footer-section" id="quick-contact">
    <h3 class="footer-title">Quick Contact</h3>
    <form class="contact-form">
        <input type="text" placeholder="Name" required>
        <input type="email" placeholder="E-mail" required>
        <textarea placeholder="Message" required></textarea>
        <button type="submit">Send</button>
    </form>
</div>
        <!-- Section Horaires d'ouverture -->
        <div class="footer-section">
            <h3 class="footer-title">Opening hours</h3>
            <ul class="opening-hours">
                <li>Monday – Thursday<span>6:00 a.m – 11:30 p.m</span></li>
                <li>Friday<span>6:00 a.m – 11:30 p.m</span></li>
                <li>Saturday <span>6:00 a.m– 11:00 p.m</span></li>
                <li>Sunday<span>6:00 a.m – 11:00 p.m</span></li>
            </ul>

        </div>
    </div>

    <!-- Footer Bottom -->
    <div class="footer-bottom">
        <div class="footer-bottom-container">
            <p>&copy; 2025 Kibris Aydin Hospital. Your Company, Inc. All rights reserved.</p>
            <div class="footer-links-grid">
                <!-- Section Exams -->
                <div class="footer-links">
                    <h3 class="footer-subtitle">Exams</h3>
                    <ul>
                        <li><a href="#">3D Mammography</a></li>
                        <li><a href="#">Amniocentesis</a></li>
                        <li><a href="#">Calcium Score</a></li>
                        <li><a href="#">Cardiac MRI</a></li>
                    </ul>
                </div>

                <!-- Section Support -->
                <div class="footer-links">
                    <h3 class="footer-subtitle">Support</h3>
                    <ul>
                        <li><a href="#">FAQ</a></li>
                        <li><a href="#">Contact Us</a></li>
                        <li><a href="#">Help Center</a></li>
                        <li><a href="#">Live Chat</a></li>
                    </ul>
                </div>

                <!-- Section Company -->
                <div class="footer-links">
                    <h3 class="footer-subtitle">Company</h3>
                    <ul>
                        <li><a href="#">About Us</a></li>
                        <li><a href="#">Careers</a></li>
                        <li><a href="#">Press</a></li>
                        <li><a href="#">Blog</a></li>
                    </ul>
                </div>

                <!-- Section Legal -->
                <div class="footer-links">
                    <h3 class="footer-subtitle">Legal</h3>
                    <ul>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms of Service</a></li>
                        <li><a href="#">Cookie Policy</a></li>
                        <li><a href="#">Disclaimer</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-contact">
    <p>Support Service | Emergency Services</p>
    <p>Call | +90 533 778 6892</p>
    <div class="follow-us">
        <p>Follow us</p>
        <div class="social-icons">
            <a href="#" target="_blank"><img src="img/fb1.png" alt="Facebook"></a>
            <a href="#" target="_blank"><img src="img/insta1.png" alt="Instagram"></a>
            <a href="#" target="_blank"><img src="img/x.png" alt="X"></a>
            <a href="#" target="_blank"><img src="img/in.png" alt="LinkedIn"></a>
            <a href="#" target="_blank"><img src="img/youtube.png" alt="YouTube"></a>
        </div>
    </div>
</div>

        </div>
    </div>
</footer>
</html>