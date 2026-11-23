import sys

content = """                  {/* --- DESKTOP VIEW --- */}
                  <div
                    className={`hidden lg:grid lg:p-4 ${VEHICLE_GRID_COLUMNS} lg:divide-x lg:divide-[#252525]`}
                  >
                    {/* VEHICLE */}
                    <div
                      className="col-span-3 md:col-span-1 flex items-center gap-3 cursor-pointer min-w-0 lg:pr-3.5"
                      onClick={() =>
                        onVehicleSelect && onVehicleSelect(vehicle.vinnumber)
                      }
                    >
                      <span
                        className="w-2.5 h-2.5 rounded-full shrink-0"
                        style={{
                          background: dotColor,
                          boxShadow: `0 0 8px ${hexToRgba(dotColor, 0.35)}`,
                        }}
                      />
                      <div
                        className="w-[clamp(44px,13vw,56px)] h-[clamp(44px,13vw,56px)] md:w-12 md:h-12 shrink-0 rounded-[10px] flex items-center justify-center text-[#FF9500]"
                        style={{
                          background: "rgba(255,149,0,0.05)",
                          border: "1px solid rgba(255,149,0,0.35)",
                        }}
                      >
                        <Bike className="w-[clamp(24px,7vw,32px)] h-[clamp(24px,7vw,32px)] md:w-6 md:h-6" />
                      </div>
                      <div className="min-w-0">
                        <div className="font-Kanit text-[clamp(17px,4.6vw,21px)] md:text-[17px] text-white  leading-tight truncate">
                          {vehicle.ownername || "Unknown owner"}
                        </div>
                        <div className="flex items-center gap-1 text-[clamp(13px,3.6vw,15px)] md:text-[14px] text-blue-500 font-Kanit mt-1 min-w-0">
                          <span className="truncate">{vehicle.vinnumber}</span>
                          <button
                            type="button"
                            aria-label="Copy VIN"
                            onClick={(e) => {
                              e.stopPropagation();
                              if (navigator.clipboard)
                                navigator.clipboard.writeText(
                                  String(vehicle.vinnumber)
                                );
                            }}
                            className="shrink-0 text-[#6B7280] hover:text-[#FF9500] transition-colors"
                          >
                            <CopyIcon className="w-[clamp(16px,4.4vw,20px)] h-[clamp(16px,4.4vw,20px)] md:w-[12px] md:h-[12px]" />
                          </button>
                        </div>
                      </div>
                    </div>

                    {/* BATTERY */}
                    <div className="lg:px-3.5 flex flex-col justify-center min-w-0 col-span-3 md:col-span-1">
                      <div className="mt-1 md:mt-2 flex items-center gap-2 min-w-0">
                        <BatteryShape
                          percent={view.socPercent}
                          color={view.voltageColor}
                        />
                        <span
                          className="text-[clamp(17px,4.6vw,22px)] md:text-[17px] font-Kanit  leading-none whitespace-nowrap shrink-0"
                          style={{ color: view.voltageColor }}
                        >
                          {view.socDisplay}
                          {view.hasSoc ? "%" : ""}
                        </span>
                      </div>
                      <div className="text-[clamp(17px,4.6vw,22px)] md:text-[17px] text-[#ffffff] mt-1.5 md:mt-2.5">
                        {view.voltageDisplay}
                      </div>
                    </div>

                    {/* USAGE */}
                    <div className="lg:px-3.5 flex flex-col justify-center min-w-0 col-span-3 md:col-span-1">
                      <div className="flex items-start gap-4 md:gap-16 mt-1 md:mt-2 min-w-0">
                        <div className="min-w-0">
                          <div className="text-[clamp(17px,4.6vw,22px)] md:text-[17px] text-white font-Kanit  leading-tight whitespace-nowrap mt-1 md:mt-2">
                            {formatKm(view.today)}
                          </div>
                        </div>
                        <div className="min-w-0">
                          <div className="text-[clamp(17px,4.6vw,22px)] md:text-[17px] text-white font-Kanit  leading-tight whitespace-nowrap mt-1 md:mt-2">
                            {formatKm(view.lastDay)}
                          </div>
                        </div>
                      </div>
                      <div className="mt-1.5 w-full min-w-0">
                        <UsageSparkline
                          id={`usage-${vehicle.vinnumber}`}
                          data={usageSeries}
                        />
                      </div>
                    </div>

                    {/* ODOMETER + REGEN */}
                    <div className="lg:px-3.5 flex flex-col justify-center min-w-0">
                      <div className="text-[clamp(15px,4.2vw,18px)] md:text-[17px] text-white font-Kanit  leading-tight whitespace-nowrap truncate mt-1 md:mt-2">
                        {formatKm(vehicle.odometer)}
                      </div>
                      <div className="text-[clamp(12px,3.4vw,14px)] md:text-[14px] font-medium text-[#FF9500] mt-1.5 whitespace-nowrap truncate">
                        Regen: {formatRegen(vehicle.recoengine)}
                      </div>
                    </div>

                    {/* ACTIVITY */}
                    <div className="lg:px-3.5 flex flex-col justify-center min-w-0 col-span-2 md:col-span-1">
                      <div className="grid grid-cols-1 gap-2.5 md:grid-cols-2 md:gap-5 mt-2 min-w-0">
                        <div className="min-w-0">
                          <div className="flex items-center gap-1.5">
                            <ClockIcon className="w-[clamp(18px,5vw,22px)] h-[clamp(18px,5vw,22px)] md:w-[18px] md:h-[18px] text-[#FF9500] shrink-0 " />
                            <div className="text-[clamp(17px,4.6vw,21px)] md:text-[17px] text-white font-Kanit  leading-tight whitespace-nowrap mt-1 ">
                              {formatUptime(vehicle.uptime)}
                            </div>
                          </div>
                        </div>
                        <div className="min-w-0">
                          <div className="flex items-center gap-1.5">
                            <CalendarIcon className="w-[clamp(18px,5vw,22px)] h-[clamp(18px,5vw,22px)] md:w-[18px] md:h-[18px] text-[#FF9500] shrink-0" />
                            <div className="text-[clamp(17px,4.6vw,21px)] md:text-[17px] text-white font-Kanit  leading-tight whitespace-nowrap mt-1.5">
                              {formatLastUpdatedTime(vehicle.lastupdated)}
                            </div>
                          </div>
                          <div className="text-[clamp(15px,4.2vw,19px)] md:text-[17px] font-normal text-[#ffffff] mt-1 whitespace-nowrap ml-5">
                            {formatLastUpdatedDate(vehicle.lastupdated)}
                          </div>
                        </div>
                      </div>
                    </div>

                    {/* LOCATION */}
                    <div className="lg:px-3.5 flex flex-col justify-center min-w-0 col-span-3 md:col-span-1">
                      {view.locationData ? (
                        <div className="mt-1 md:mt-2 flex justify-end md:justify-start lg:items-start">
                          <a
                            href={view.locationData.mapsUrl}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-flex items-center justify-center gap-1.5 h-[clamp(44px,12.5vw,52px)] md:h-[48px] w-full max-w-[clamp(96px,26vw,124px)] md:max-w-[104px] rounded-[10px] bg-[rgba(255,149,0,0.05)] border border-[rgba(255,149,0,0.45)] text-[#FF9500] text-[clamp(12px,3.4vw,14px)] md:text-[12px] font-medium hover:bg-[rgba(255,149,0,0.12)] transition-colors"
                          >
                            <MapPinIcon className="w-[clamp(16px,4.6vw,20px)] h-[clamp(16px,4.6vw,20px)] md:w-4 md:h-4" />
                            Map
                          </a>
                        </div>
                      ) : (
                        <div className="text-[12px] text-gray-600 mt-2">
                          —
                        </div>
                      )}
                    </div>
                  </div>

                  {/* === MOBILE VIEW === */}
                  <div className="flex flex-col lg:hidden p-[clamp(12px,3.4vw,18px)] md:p-4">
                    {/* Row 1: Vehicle */}
                    <div className="flex justify-between items-start pb-4 border-b border-[#252525]">
                      <div 
                        className="flex items-center gap-3 cursor-pointer min-w-0"
                        onClick={() => onVehicleSelect && onVehicleSelect(vehicle.vinnumber)}
                      >
                        <span
                          className="w-2.5 h-2.5 rounded-full shrink-0 mt-1"
                          style={{
                            background: dotColor,
                            boxShadow: `0 0 8px ${hexToRgba(dotColor, 0.35)}`,
                          }}
                        />
                        <div
                          className="w-[clamp(44px,13vw,56px)] h-[clamp(44px,13vw,56px)] shrink-0 rounded-[10px] flex items-center justify-center text-[#FF9500]"
                          style={{
                            background: "rgba(255,149,0,0.05)",
                            border: "1px solid rgba(255,149,0,0.35)",
                          }}
                        >
                          <Bike className="w-[clamp(24px,7vw,32px)] h-[clamp(24px,7vw,32px)]" />
                        </div>
                        <div className="min-w-0">
                          <div className="font-Kanit text-[clamp(17px,4.6vw,21px)] text-white leading-tight truncate">
                            {vehicle.ownername || "Unknown owner"}
                          </div>
                          <div className="flex items-center gap-1 text-[clamp(13px,3.6vw,15px)] text-blue-500 font-Kanit mt-1 min-w-0">
                            <span className="truncate">{vehicle.vinnumber}</span>
                            <button
                              type="button"
                              aria-label="Copy VIN"
                              onClick={(e) => {
                                e.stopPropagation();
                                if (navigator.clipboard)
                                  navigator.clipboard.writeText(String(vehicle.vinnumber));
                              }}
                              className="shrink-0 text-[#6B7280] hover:text-[#FF9500] transition-colors"
                            >
                              <CopyIcon className="w-[clamp(14px,4.4vw,20px)] h-[clamp(14px,4.4vw,20px)]" />
                            </button>
                          </div>
                        </div>
                      </div>
                      <div className="flex items-center gap-2 shrink-0">
                        <BatteryShape percent={view.socPercent} color={view.voltageColor} />
                        <span
                          className="font-Kanit text-[clamp(16px,4.6vw,20px)]"
                          style={{ color: view.voltageColor }}
                        >
                          {view.socDisplay}{view.hasSoc ? "%" : ""}
                        </span>
                      </div>
                    </div>

                    {/* Row 2: Battery & Usage */}
                    <div className="grid grid-cols-[auto_1fr] gap-4 pt-4 pb-4 border-b border-[#252525]">
                      <div className="flex flex-col border-r border-[#252525] pr-4">
                        <div className="text-[12px] text-[#929292] mb-1.5">Battery</div>
                        <div className="flex items-center gap-2">
                          <BatteryShape percent={view.socPercent} color={view.voltageColor} />
                          <span
                            className="font-Kanit text-[16px]"
                            style={{ color: view.voltageColor }}
                          >
                            {view.socDisplay}{view.hasSoc ? "%" : ""}
                          </span>
                        </div>
                        <div className="text-[17px] text-white mt-1.5">{view.voltageDisplay}</div>
                      </div>
                      <div className="flex flex-col pl-1">
                        <div className="flex gap-6">
                          <div className="flex flex-col">
                            <div className="text-[12px] text-[#929292] mb-1.5">Today</div>
                            <div className="font-Kanit text-[17px] text-white leading-tight">{formatKm(view.today)}</div>
                          </div>
                          <div className="flex flex-col">
                            <div className="text-[12px] text-[#929292] mb-1.5">Last Day</div>
                            <div className="font-Kanit text-[17px] text-white leading-tight">{formatKm(view.lastDay)}</div>
                          </div>
                        </div>
                        <div className="mt-1.5 w-full">
                          <UsageSparkline id={`usage-mob-${vehicle.vinnumber}`} data={usageSeries} />
                        </div>
                      </div>
                    </div>

                    {/* Row 3: Odometer, Regen, Uptime */}
                    <div className="grid grid-cols-[1fr_1fr_auto] gap-2 pt-4 pb-4 border-b border-[#252525]">
                      <div className="flex flex-col">
                        <div className="text-[12px] text-[#929292] mb-1.5">Odometer</div>
                        <div className="font-Kanit text-[17px] text-white">{formatKm(vehicle.odometer)}</div>
                      </div>
                      <div className="flex flex-col">
                        <div className="text-[12px] text-[#929292] mb-1.5">Regen</div>
                        <div className="font-Kanit text-[17px] text-[#FF9500]">{formatRegen(vehicle.recoengine)}</div>
                      </div>
                      <div className="flex flex-col border-l border-[#252525] pl-4 min-w-[100px]">
                        <div className="flex items-center gap-1.5 mb-1.5 text-[#929292]">
                          <ClockIcon className="w-4 h-4" />
                          <span className="text-[12px]">Uptime</span>
                        </div>
                        <div className="flex items-center gap-1.5 text-white">
                          <ClockIcon className="w-[18px] h-[18px]" />
                          <span className="font-Kanit text-[17px]">{formatUptime(vehicle.uptime)}</span>
                        </div>
                      </div>
                    </div>

                    {/* Row 4: Last Update & Map */}
                    <div className="flex justify-between items-center pt-4">
                      <div className="flex flex-col">
                        <div className="text-[12px] text-[#929292] mb-1.5">Last Update</div>
                        <div className="flex items-start gap-1.5">
                          <CalendarIcon className="w-[18px] h-[18px] text-white mt-0.5" />
                          <div className="flex flex-col">
                            <div className="font-Kanit text-[17px] text-white leading-tight mb-1">{formatLastUpdatedTime(vehicle.lastupdated)}</div>
                            <div className="text-[14px] text-white">{formatLastUpdatedDate(vehicle.lastupdated)}</div>
                          </div>
                        </div>
                      </div>
                      <div>
                        {view.locationData ? (
                          <a
                            href={view.locationData.mapsUrl}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-flex items-center justify-center gap-1.5 h-[44px] px-6 rounded-[10px] bg-[rgba(255,149,0,0.05)] border border-[rgba(255,149,0,0.45)] text-[#FF9500] text-[14px] font-medium hover:bg-[rgba(255,149,0,0.12)] transition-colors"
                          >
                            <MapPinIcon className="w-4 h-4" />
                            Map
                          </a>
                        ) : (
                          <div className="text-[14px] text-gray-600">—</div>
                        )}
                      </div>
                    </div>
                  </div>"""

with open(r'c:\Users\Umesh\adition-pro\displaydashbord\command\src\Components\Nxdetails\index.js', 'r', encoding='utf-8') as f:
    lines = f.readlines()

lines[1261:1466] = [content + '\n']

with open(r'c:\Users\Umesh\adition-pro\displaydashbord\command\src\Components\Nxdetails\index.js', 'w', encoding='utf-8') as f:
    f.writelines(lines)
