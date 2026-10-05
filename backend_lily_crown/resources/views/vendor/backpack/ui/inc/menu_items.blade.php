{{-- This file is used for menu items by any Backpack v7 theme --}}
<li class="nav-item"><a class="nav-link" href="{{ backpack_url('dashboard') }}"><i class="la la-home nav-icon"></i> {{ trans('backpack::base.dashboard') }}</a></li>

<x-backpack::menu-separator title="Sales" />
<x-backpack::menu-item title="Orders" icon="la la-shopping-cart" :link="backpack_url('order')" />
<x-backpack::menu-item title="Coupons" icon="la la-tags" :link="backpack_url('coupon')" />
<x-backpack::menu-item title="Customers" icon="la la-users" :link="backpack_url('customer')" />

<x-backpack::menu-separator title="Catalog" />
<x-backpack::menu-item title="Products" icon="la la-tshirt" :link="backpack_url('product')" />
<x-backpack::menu-item title="Categories" icon="la la-th-large" :link="backpack_url('category')" />

<x-backpack::menu-separator title="Website" />
<x-backpack::menu-item title="Hero slides" icon="la la-image" :link="backpack_url('hero-slide')" />
<x-backpack::menu-item title="Lookbook" icon="la la-camera" :link="backpack_url('lookbook')" />
<x-backpack::menu-item title="Testimonials" icon="la la-comment" :link="backpack_url('testimonial')" />
<x-backpack::menu-item title="Features" icon="la la-star" :link="backpack_url('feature')" />
<x-backpack::menu-item title="Instagram" icon="la la-instagram" :link="backpack_url('instagram-post')" />
<x-backpack::menu-item title="Blog" icon="la la-newspaper" :link="backpack_url('blog')" />
<x-backpack::menu-item title="Subscribers" icon="la la-envelope" :link="backpack_url('subscriber')" />
<x-backpack::menu-item title="Site settings" icon="la la-cog" :link="backpack_url('site-setting')" />
