/** @type {import('tailwindcss').Config} */
export default {
  content: ["./index.html", "./src/**/*.{vue,js,ts,jsx,tsx}"],
  // theme: {
  //   screens: {
  //     xs: "480px",
  //     ...defaultTheme.screens,
  //   },
  // },
  theme: {
    extend: {
      fontFamily: {
        sans: ["Inter", "sans-serif"], // Replace 'Inter' with your chosen font
      },
    },
    screens: {
      xs: "480px",
      sm: "640px",
      md: "768px",
      lg: "1024px",
      xl: "1280px",
      "2xl": "1536px",
    },
  },
  plugins: [],
};
