"use strict";(self.webpackChunklansuite_documentation=self.webpackChunklansuite_documentation||[]).push([[605],{5942:(e,t,n)=>{n.r(t),n.d(t,{assets:()=>r,contentTitle:()=>s,default:()=>h,frontMatter:()=>c,metadata:()=>o,toc:()=>d});var a=n(5893),i=n(1151);const c={id:"cache",title:"Using the LANSuite cache",sidebar_position:2},s=void 0,o={id:"development/cache",title:"Using the LANSuite cache",description:"Introduction",source:"@site/docs/development/cache.md",sourceDirName:"development",slug:"/development/cache",permalink:"/lansuite/docs/development/cache",draft:!1,unlisted:!1,editUrl:"https://github.com/lansuite/lansuite/tree/master/website/docs/development/cache.md",tags:[],version:"current",sidebarPosition:2,frontMatter:{id:"cache",title:"Using the LANSuite cache",sidebar_position:2},sidebar:"documentationSidebar",previous:{title:"Generating API docs",permalink:"/lansuite/docs/development/api-docs"},next:{title:"Coding style guide",permalink:"/lansuite/docs/development/coding-style-guide"}},r={},d=[{value:"Introduction",id:"introduction",level:2},{value:"Usage",id:"usage",level:2},{value:"Architecture",id:"architecture",level:3},{value:"Code",id:"code",level:3},{value:"Naming convention",id:"naming-convention",level:3},{value:"Pitfalls",id:"pitfalls",level:2},{value:"Race conditions",id:"race-conditions",level:3},{value:"Cache Item Timeout",id:"cache-item-timeout",level:3},{value:"Cached display",id:"cached-display",level:3}];function l(e){const t={a:"a",code:"code",h2:"h2",h3:"h3",li:"li",p:"p",pre:"pre",ul:"ul",...(0,i.a)(),...e.components};return(0,a.jsxs)(a.Fragment,{children:[(0,a.jsx)(t.h2,{id:"introduction",children:"Introduction"}),"\n",(0,a.jsx)(t.p,{children:"In order to avoid unnecessary calculations of unchanged data or retrieval from slow sources LanSuite now uses an internal caching infrastructure.\nWhen you are implementing new features it should be considered if content needs to be created on every call or if content can be cached and reused.\nAlso for the usage this has some implications as the content on some pages may not represent the latest state, but the cached content."}),"\n",(0,a.jsx)(t.h2,{id:"usage",children:"Usage"}),"\n",(0,a.jsx)(t.h3,{id:"architecture",children:"Architecture"}),"\n",(0,a.jsx)(t.p,{children:'Think where data needs to be processed on call and where it requires processing on change.\nE.g. A user is set to "paid" for the next party. This means that the amount of guests increases by one.\nEither the total number of guests is recalculated on every page display again, or this is updated once the amount changes by user payment.\nThe more efficient variant is the later one, as the case appears far less than just page displays.\nAlso there is no requirement that the value is always correct, as it is just used for display.'}),"\n",(0,a.jsx)(t.h3,{id:"code",children:"Code"}),"\n",(0,a.jsxs)(t.p,{children:["There is object named ",(0,a.jsx)(t.code,{children:"$cache"})," available in the global scope.\nThis implements ",(0,a.jsx)(t.a,{href:"https://www.php-fig.org/psr/psr-16/",children:"PSR-16"})," and either works via APCu (if module enabled) or with files in the temporary directory.\nCode example:"]}),"\n",(0,a.jsx)(t.pre,{children:(0,a.jsx)(t.code,{className:"language-php",children:"// Import global object\nglobal $cache;\n\n// Try to get cache-item\n$cachedItem = $cache->getItem('module.entry.id');\nif (!$cachedItem->isHit()) {\n    // Not in cache, thus generate content\n    $cacheData = expensiveFunction();\n    // Write back to item and cache\n    $cachedItem->set($cacheData,<TTL>);\n    $cache->save($cachedItem);\n    }\n$Data = $cachedItem->get();\n//Run processing of data in $Data\n...\n"})}),"\n",(0,a.jsx)(t.h3,{id:"naming-convention",children:"Naming convention"}),"\n",(0,a.jsxs)(t.p,{children:["Cache entries should be named based on the following schema:\n",(0,a.jsx)(t.code,{children:"<module>.<entry>.<identificator>"}),"\ne.g. ",(0,a.jsx)(t.code,{children:"discord.cache"}),", ",(0,a.jsx)(t.code,{children:"forum.thread.32121"})," or ",(0,a.jsx)(t.code,{children:"translation.de.board"}),".\nFurther levels below this are possible and left to the discretion of the developer."]}),"\n",(0,a.jsx)(t.h2,{id:"pitfalls",children:"Pitfalls"}),"\n",(0,a.jsx)(t.h3,{id:"race-conditions",children:"Race conditions"}),"\n",(0,a.jsxs)(t.p,{children:["As the cache provides a single instance across parallel executions, it can happen that multiple threads access an entry at the same or close to the same time.\nThis could cause a cache entry to be updated multiple times, because an update already occured between ",(0,a.jsx)(t.code,{children:"$cache->getItem()"})," and ",(0,a.jsx)(t.code,{children:"$cache->write()"}),"\nThis can be a problem on high-load servers on restarts or cache misses, because this could cause a massive ammount of recalculations.\nThis would need a better implementation that includes cache mutexes."]}),"\n",(0,a.jsx)(t.h3,{id:"cache-item-timeout",children:"Cache Item Timeout"}),"\n",(0,a.jsxs)(t.p,{children:["Entries have a default Time-To-Live of ten minutes and disappear after that.\nA cache entry could disappear between the check in ",(0,a.jsx)(t.code,{children:"$cache->getItem()"})," and retrieval if the TTL is breached between these two calls.\nThis can be influenced by defining a custom TTL when writing the item back."]}),"\n",(0,a.jsx)(t.h3,{id:"cached-display",children:"Cached display"}),"\n",(0,a.jsx)(t.p,{children:"Please remember that while developing your software with cache usage you may see stale data when displaying a page.\nIt would be recommended to either:"}),"\n",(0,a.jsxs)(t.ul,{children:["\n",(0,a.jsx)(t.li,{children:"reduce the TTL to one second"}),"\n",(0,a.jsxs)(t.li,{children:["overwrite $cache with an instance of ",(0,a.jsx)(t.code,{children:"NullCache"})," as that practically disables the cache."]}),"\n",(0,a.jsxs)(t.li,{children:["include a call to ",(0,a.jsx)(t.code,{children:"$cache->clear()"})," to ensure that no old entries are in the cache"]}),"\n"]})]})}function h(e={}){const{wrapper:t}={...(0,i.a)(),...e.components};return t?(0,a.jsx)(t,{...e,children:(0,a.jsx)(l,{...e})}):l(e)}},1151:(e,t,n)=>{n.d(t,{Z:()=>o,a:()=>s});var a=n(7294);const i={},c=a.createContext(i);function s(e){const t=a.useContext(c);return a.useMemo((function(){return"function"==typeof e?e(t):{...t,...e}}),[t,e])}function o(e){let t;return t=e.disableParentContext?"function"==typeof e.components?e.components(i):e.components||i:s(e.components),a.createElement(c.Provider,{value:t},e.children)}}}]);