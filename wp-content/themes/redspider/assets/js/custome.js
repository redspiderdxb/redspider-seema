var swiper = new Swiper(".whySwiper", {
  slidesPerView: 1,
  spaceBetween: 10,
  loop: true,
  speed: 4000, // higher = smoother continuous motion

  autoplay: {
    delay: 0, // IMPORTANT: no pause between slides
    disableOnInteraction: false,
    pauseOnMouseEnter: true, // ✅ pause on hover
  },

  freeMode: false, // smooth continuous scrolling
  freeModeMomentum: false,

  pagination: {
    el: ".swiper-pagination",
    clickable: true,
  },

  breakpoints: {
    640: {
      slidesPerView: 2,
      spaceBetween: 20,
    },
    768: {
      slidesPerView: 3,
      spaceBetween: 40,
    },
    1024: {
      slidesPerView: 4,
      spaceBetween: 15,
    },
  },
});



// hosting seiper


var swiper = new Swiper(".happSwiper", {
  slidesPerView: 3,
  spaceBetween: 10,
  loop: true,
  speed: 4000, // higher = smoother continuous motion

  autoplay: {
    delay: 0, // IMPORTANT: no pause between slides
    disableOnInteraction: false,
    pauseOnMouseEnter: true, // ✅ pause on hover
  },

  freeMode: false, // smooth continuous scrolling
  freeModeMomentum: false,

  pagination: {
    el: ".swiper-pagination",
    clickable: true,
  },

  breakpoints: {
    640: {
      slidesPerView: 3,
      spaceBetween: 20,
    },
    768: {
      slidesPerView: 3,
      spaceBetween: 40,
    },
    1024: {
      slidesPerView: 8,
      spaceBetween: 15,
    },
  },
});




    

    var swiper = new Swiper(".homeSwiper", {
      spaceBetween: 30,
      centeredSlides: true,
      autoplay: {
        delay: 2500,
        disableOnInteraction: false,
      },
      pagination: {
        el: ".swiper-pagination",
        clickable: true,
      },
      navigation: {
        nextEl: ".swiper-button-next",
        prevEl: ".swiper-button-prev",
      },
    });





    var swiper = new Swiper(".whySwiper-home", {
  slidesPerView: 1,
  spaceBetween: 10,
  loop: true,
  speed: 4000, // higher = smoother continuous motion

  autoplay: {
    delay: 0, // IMPORTANT: no pause between slides
    disableOnInteraction: false,
    pauseOnMouseEnter: true, // ✅ pause on hover
  },

  freeMode: false, // smooth continuous scrolling
  freeModeMomentum: false,

  pagination: {
    el: ".swiper-pagination",
    clickable: true,
  },

  breakpoints: {
    640: {
      slidesPerView: 2,
      spaceBetween: 20,
    },
    768: {
      slidesPerView: 3,
      spaceBetween: 40,
    },
    1024: {
      slidesPerView: 4,
      spaceBetween: 15,
    },
  },
});
