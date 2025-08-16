<?php
require_once 'config/config.php';
$pageTitle = "About";
?>

<!DOCTYPE html>
<html lang="<?php echo DEFAULT_LANGUAGE; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="<?php echo DEFAULT_LANGUAGE === 'ar' ? 'rtl' : 'ltr'; ?>">
    <?php include 'includes/header.php'; ?>
    
    <main class="container mt-4">
        <!-- Hero Section -->
        <div class="card mb-4">
            <div class="card-body text-center py-5">
                <h1 class="display-4 mb-3"><?php echo SITE_NAME; ?></h1>
                <p class="lead mb-4">
                    Enhancing the Hajj and Umrah experience through technology and innovation.
                </p>
                <div class="mb-3">
                    <i class="fas fa-kaaba fa-4x text-primary"></i>
                </div>
            </div>
        </div>
        
        <!-- Mission and Vision -->
        <div class="row mb-4">
            <div class="col-md-6 mb-4 mb-md-0">
                <div class="card h-100">
                    <div class="card-body">
                        <h2 class="card-title text-primary"><i class="fas fa-bullseye me-2"></i> Our Mission</h2>
                        <p class="card-text">
                            To provide pilgrims with a safe, well-guided, and supportive environment for performing Hajj and Umrah rituals.
                            We aim to reduce the challenges related to overcrowding, disorientation, and emergencies through 
                            innovative technology solutions.
                        </p>
                        <p class="card-text">
                            Our platform connects pilgrims with their companions, guides them through rituals, and ensures 
                            immediate assistance during emergencies, making the sacred journey more fulfilling and secure.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h2 class="card-title text-primary"><i class="fas fa-eye me-2"></i> Our Vision</h2>
                        <p class="card-text">
                            To transform the Hajj and Umrah experience through a digital ecosystem that addresses the key challenges 
                            faced by millions of pilgrims each year. We envision a future where technology seamlessly integrates 
                            with spirituality to enhance the pilgrimage experience.
                        </p>
                        <p class="card-text">
                            We support Saudi Arabia's Vision 2030 by contributing to the development of smart infrastructure 
                            and services for pilgrims, improving safety, accessibility, and organization of the holy sites.
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Key Features -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h2 class="mb-0">Key Features</h2>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="text-center mb-3">
                            <i class="fas fa-map-marked-alt fa-3x text-primary"></i>
                        </div>
                        <h3 class="h4 text-center">Real-time Tracking</h3>
                        <p>
                            Keep track of your group members through GPS location sharing. Never lose sight of your family 
                            or travel companions in crowded areas.
                        </p>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center mb-3">
                            <i class="fas fa-exclamation-triangle fa-3x text-primary"></i>
                        </div>
                        <h3 class="h4 text-center">Emergency Support</h3>
                        <p>
                            Quickly report missing persons and medical emergencies with precise location data. 
                            Get immediate assistance from authorities with our streamlined emergency response system.
                        </p>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center mb-3">
                            <i class="fas fa-book fa-3x text-primary"></i>
                        </div>
                        <h3 class="h4 text-center">Ritual Guidance</h3>
                        <p>
                            Follow step-by-step instructions for Tawaf, Sa'i, and other rituals. Track your progress 
                            and access relevant prayers and supplications along the way.
                        </p>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center mb-3">
                            <i class="fas fa-wheelchair fa-3x text-primary"></i>
                        </div>
                        <h3 class="h4 text-center">Accessibility Services</h3>
                        <p>
                            Request carts, wheelchairs, and other accessibility services for elderly or disabled pilgrims. 
                            Connect with licensed service providers at the click of a button.
                        </p>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center mb-3">
                            <i class="fas fa-id-card fa-3x text-primary"></i>
                        </div>
                        <h3 class="h4 text-center">Licensed Services</h3>
                        <p>
                            Verify the credentials of service providers and access only authorized personnel. 
                            Report unlicensed workers to maintain safety and quality standards.
                        </p>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center mb-3">
                            <i class="fas fa-clock fa-3x text-primary"></i>
                        </div>
                        <h3 class="h4 text-center">Prayer Times & Qibla</h3>
                        <p>
                            Get accurate prayer times based on your location and find the Qibla direction anywhere 
                            in the holy sites. Never miss a prayer during your pilgrimage.
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- How It Works -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h2 class="mb-0">How It Works</h2>
            </div>
            <div class="card-body">
                <div class="row align-items-center mb-4">
                    <div class="col-md-6 order-md-2 mb-3 mb-md-0">
                        <img src="assets/images/step1.jpg" alt="Registration" class="img-fluid rounded">
                    </div>
                    <div class="col-md-6 order-md-1">
                        <h3 class="h4"><span class="badge bg-success me-2">1</span> Register & Create Groups</h3>
                        <p>
                            Sign up with your basic information and create a group for your family or travel companions. 
                            Invite others to join your group so you can stay connected throughout your journey.
                        </p>
                        <p>
                            Group leaders can monitor the location of all members, receive alerts when someone strays too far, 
                            and coordinate meeting points efficiently.
                        </p>
                    </div>
                </div>
                
                <div class="row align-items-center mb-4">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <img src="assets/images/step2.jpg" alt="Track & Connect" class="img-fluid rounded">
                    </div>
                    <div class="col-md-6">
                        <h3 class="h4"><span class="badge bg-success me-2">2</span> Track & Connect</h3>
                        <p>
                            Enable location sharing to see the real-time position of your group members on an interactive map. 
                            The app works both indoors and outdoors across all holy sites.
                        </p>
                        <p>
                            If someone gets separated, you can quickly locate them or report them as missing if they can't be found. 
                            Authorities can access their last known location and photo to assist in finding them.
                        </p>
                    </div>
                </div>
                
                <div class="row align-items-center mb-4">
                    <div class="col-md-6 order-md-2 mb-3 mb-md-0">
                        <img src="assets/images/step3.jpg" alt="Ritual Guidance" class="img-fluid rounded">
                    </div>
                    <div class="col-md-6 order-md-1">
                        <h3 class="h4"><span class="badge bg-success me-2">3</span> Follow Ritual Guidance</h3>
                        <p>
                            Access detailed step-by-step instructions for each ritual, complete with tracking counters for 
                            Tawaf and Sa'i. The app provides relevant prayers and supplications at each stage.
                        </p>
                        <p>
                            Never worry about missing a step or losing count during your rituals. The platform keeps track of 
                            your progress and provides guidance based on your location.
                        </p>
                    </div>
                </div>
                
                <div class="row align-items-center">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <img src="assets/images/step4.jpg" alt="Emergency Response" class="img-fluid rounded">
                    </div>
                    <div class="col-md-6">
                        <h3 class="h4"><span class="badge bg-success me-2">4</span> Get Help When Needed</h3>
                        <p>
                            In case of emergencies, request medical help, report missing persons, or request a cart or 
                            wheelchair with just a few taps. Your exact location is automatically shared with responders.
                        </p>
                        <p>
                            The platform connects only with licensed service providers and authorized personnel, ensuring 
                            your safety and the quality of services you receive.
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Testimonials -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h2 class="mb-0">User Testimonials</h2>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="mb-3 text-warning">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                </div>
                                <p class="card-text fst-italic">
                                    "This platform was a lifesaver! My elderly mother wandered off during Tawaf, 
                                    and I was able to locate her quickly using the tracking feature. The authorities 
                                    were also incredibly responsive when I reported her missing."
                                </p>
                                <div class="d-flex align-items-center mt-3">
                                    <div class="flex-shrink-0">
                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                                             style="width: 50px; height: 50px;">
                                            <span class="h5 mb-0">A</span>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h5 class="mb-0">Ahmed Malik</h5>
                                        <p class="mb-0 text-muted small">From Pakistan</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="mb-3 text-warning">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                </div>
                                <p class="card-text fst-italic">
                                    "The ritual guidance was incredible! As a first-time pilgrim, I was nervous about 
                                    performing the rituals correctly. The step-by-step instructions and counter features 
                                    gave me confidence that I was doing everything properly."
                                </p>
                                <div class="d-flex align-items-center mt-3">
                                    <div class="flex-shrink-0">
                                        <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" 
                                             style="width: 50px; height: 50px;">
                                            <span class="h5 mb-0">F</span>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h5 class="mb-0">Fatima Rahman</h5>
                                        <p class="mb-0 text-muted small">From Indonesia</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="mb-3 text-warning">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="far fa-star"></i>
                                </div>
                                <p class="card-text fst-italic">
                                    "We needed a wheelchair for my father who couldn't walk long distances. The cart 
                                    service request feature connected us with a licensed provider who arrived within 
                                    minutes. It made our Umrah experience so much more comfortable for him."
                                </p>
                                <div class="d-flex align-items-center mt-3">
                                    <div class="flex-shrink-0">
                                        <div class="bg-danger text-white rounded-circle d-flex align-items-center justify-content-center" 
                                             style="width: 50px; height: 50px;">
                                            <span class="h5 mb-0">M</span>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 ms-3">
                                        <h5 class="mb-0">Mohammed Abdel-Rahman</h5>
                                        <p class="mb-0 text-muted small">From Egypt</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- FAQ Section -->
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">
                <h2 class="mb-0">Frequently Asked Questions</h2>
            </div>
            <div class="card-body">
                <div class="accordion" id="faqAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingOne">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" 
                                    data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                How does the location tracking work?
                            </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse show" 
                             aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                <p>
                                    Our platform uses GPS and network-based location services to track users' positions. The location data is 
                                    securely transmitted and stored, and is only shared with members of your group or authorities in case of emergencies.
                                </p>
                                <p>
                                    The system works both indoors and outdoors across the holy sites, with accuracy typically within 5-10 meters. 
                                    Location updates occur automatically every minute but can be manually refreshed at any time.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingTwo">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                    data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                How secure is my personal information?
                            </button>
                        </h2>
                        <div id="collapseTwo" class="accordion-collapse collapse" 
                             aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                <p>
                                    We take data security very seriously. All personal information is encrypted and stored securely according to 
                                    international data protection standards. Your location data is only shared with members of your group and 
                                    authorized personnel in case of emergencies.
                                </p>
                                <p>
                                    We do not sell or share your personal information with third parties. Your data is used solely for the 
                                    purpose of providing the services you request through our platform.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingThree">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                    data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                Do I need an internet connection to use the app?
                            </button>
                        </h2>
                        <div id="collapseThree" class="accordion-collapse collapse" 
                             aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                <p>
                                    Yes, an internet connection (cellular data or Wi-Fi) is required for most features of the platform 
                                    to work properly, including location tracking, emergency reporting, and service requests.
                                </p>
                                <p>
                                    However, the ritual guidance information and basic prayers can be accessed offline if you've previously 
                                    loaded them while connected. We recommend purchasing a local SIM card with data plan upon arrival 
                                    in Saudi Arabia for the best experience.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingFour">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                    data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                How do I report a missing person?
                            </button>
                        </h2>
                        <div id="collapseFour" class="accordion-collapse collapse" 
                             aria-labelledby="headingFour" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                <p>
                                    To report a missing person, go to the "Services" section and select "Report Missing Person." 
                                    You'll need to provide details about the missing person, including their name, a recent photo 
                                    (if available), and the last known location.
                                </p>
                                <p>
                                    The report is immediately sent to authorities who will begin searching for the person. You'll 
                                    receive updates on the status of the search directly through the app. If the person is found, 
                                    you'll be notified immediately.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingFive">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                    data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                                How can I verify if a service provider is licensed?
                            </button>
                        </h2>
                        <div id="collapseFive" class="accordion-collapse collapse" 
                             aria-labelledby="headingFive" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                <p>
                                    All service providers on our platform are pre-verified and licensed. When you request a service 
                                    (such as a cart or wheelchair), you'll only be connected with authorized providers who have been 
                                    vetted by the relevant authorities.
                                </p>
                                <p>
                                    Each provider has a unique identification number and digital badge that you can verify within the app. 
                                    If you encounter someone claiming to be a service provider but not recognized by our system, you 
                                    can report them through the app.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Contact Section -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h2 class="mb-0">Contact Us</h2>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-4 mb-md-0">
                        <h3 class="h4">Get in Touch</h3>
                        <p>
                            Have questions, feedback, or need assistance with our platform? 
                            We're here to help. Use the form to send us a message, and we'll 
                            get back to you as soon as possible.
                        </p>
                        <ul class="list-unstyled">
                            <li class="mb-2">
                                <i class="fas fa-envelope me-2 text-primary"></i>
                                support@hajjsmartplatform.com
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-phone me-2 text-primary"></i>
                                +966-12-555-1234
                            </li>
                            <li>
                                <i class="fas fa-map-marker-alt me-2 text-primary"></i>
                                King Abdullah Road, Makkah, Saudi Arabia
                            </li>
                        </ul>
                        <div class="mt-4">
                            <a href="#" class="text-decoration-none me-3">
                                <i class="fab fa-facebook fa-2x text-primary"></i>
                            </a>
                            <a href="#" class="text-decoration-none me-3">
                                <i class="fab fa-twitter fa-2x text-info"></i>
                            </a>
                            <a href="#" class="text-decoration-none me-3">
                                <i class="fab fa-instagram fa-2x text-danger"></i>
                            </a>
                            <a href="#" class="text-decoration-none">
                                <i class="fab fa-youtube fa-2x text-danger"></i>
                            </a>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <form id="contactForm">
                            <div class="mb-3">
                                <label for="name" class="form-label">Your Name</label>
                                <input type="text" class="form-control" id="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="subject" class="form-label">Subject</label>
                                <select class="form-select" id="subject" required>
                                    <option value="">Select a subject</option>
                                    <option value="General Inquiry">General Inquiry</option>
                                    <option value="Technical Support">Technical Support</option>
                                    <option value="Feedback">Feedback</option>
                                    <option value="Partnerships">Partnerships</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="message" class="form-label">Message</label>
                                <textarea class="form-control" id="message" rows="4" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i> Send Message
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Contact form submission handler
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // In a real application, this would send the data to a server
            alert('Thank you for your message. We will get back to you soon!');
            this.reset();
        });
    </script>
</body>
</html>