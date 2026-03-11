<?php
$store_city = 'ESSAOUIRA';
$store_heading = 'Coop Arjana - Main Store';
$store_description = 'We are happy to welcome you at our main store in ' . $store_city . '.';

$map_src = "https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d13276.123456789!2d-9.600000!3d30.420000!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0xdb1234567890abcd%3A0xabcdef1234567890!2sAgadir%2C%20Morocco!5e0!3m2!1sen!2sma!4v1718633200000!5m2!1sen!2sma";
?>

<main>
  <h1><?php echo $store_heading; ?></h1>

  <section class="store-location">
    <div class="container">
      <h2>Our Store Location</h2>
      <p><?php echo $store_description; ?></p>

      <div class="map-container">
        <iframe
          src="<?php echo $map_src; ?>"
          title="Map to our store in <?php echo $store_city; ?>"
          width="100%"
          height="100%"
          style="border:0;"
          loading="lazy"
          allowfullscreen>
        </iframe>
      </div>
    </div>
  </section>
</main>
